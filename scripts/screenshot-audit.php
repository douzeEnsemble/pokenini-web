<?php

declare(strict_types=1);

/**
 * Visual-audit helper - NOT a PHPUnit test, NOT wired into `make tests` or
 * `make quality`. Throwaway/manual tool: walks the public routes of the
 * *dev* vhost (nginx port 8080, real dev backend data) at several viewport
 * widths, and saves one PNG per page/viewport under var/screenshots/ so
 * they can be reviewed by eye (or read back by Claude) instead of only
 * reading Twig/CSS source.
 *
 * Dev, not test (port 8081): .env.test.local has to keep pointing image URLs
 * at the real https://icon.pokenini.fr CDN, because the real PHPUnit suite
 * asserts against that exact string - overriding it there to something
 * reachable from this sandbox (no outbound internet) broke 6 real tests.
 * .env.dev.local has no such constraint, so it points POKEMON_ICON_URL & co.
 * at icons.pokenini.local instead (see docker-compose.override.yaml and the
 * `docker network connect ... pokenini-resources` note below).
 *
 * Two passes, on purpose:
 *   1. A fast plain-HTTP pass (Symfony HttpBrowser, no real browser) that
 *      logs in, discovers real album/election detail links from the list
 *      pages, and checks the real HTTP status of every candidate page -
 *      fixture data isn't always internally consistent (a list page can
 *      link to a detail page that 404s), so anything that doesn't return
 *      its expected status is dropped before we ever spin up Selenium for
 *      it.
 *   2. A Selenium/Panther pass that only screenshots the pages that
 *      survived step 1.
 *
 * Requires the stack already running (`make start`): reuses the same
 * `chrome` Selenium container the Browser test suite drives.
 *
 * Usage (from the host):
 *   docker compose exec php php scripts/screenshot-audit.php
 *   docker compose exec php php scripts/screenshot-audit.php --locale=en
 *   docker compose exec php php scripts/screenshot-audit.php --roles=trainer,admin
 *
 * Output:
 *   var/screenshots/<role>__<page-slug>__<viewport>.png
 *   var/screenshots/_cookie-banner.png (captured once, see below)
 *   (var/ is gitignored, nothing here is ever committed)
 */

use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\WebDriverDimension;
use Symfony\Component\BrowserKit\HttpBrowser;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\Panther\Client;
use Symfony\Contracts\HttpClient\HttpClientInterface;

require dirname(__DIR__).'/vendor/autoload.php';

// Two different base URIs, because this script's HTTP pass and its browser pass run
// from two different points on the Docker network:
//   - Pass 1 (HttpBrowser/HttpClient) runs in *this* php process, which is on the
//     shared pokenini-local network alongside pokenini-api/-back's own containers.
//     They all name their nginx service "web", and Compose aliases every container
//     on every network it joins with its own service name - so plain "web" round-robins
//     across all three sibling stacks from here (see tools/w3c-validate for the same
//     issue). www.pokenini.local is this project's own unambiguous alias for it.
//   - Pass 2 (Selenium/Panther) runs from the chrome/firefox containers, which are
//     deliberately NOT on pokenini-local (added once for image-loading, reverted:
//     it broke the real Browser test suite's Firefox runs the same way, same "web"
//     collision as above). From their side plain "web" is unambiguous, but
//     www.pokenini.local doesn't resolve at all since that alias only exists on
//     pokenini-local. They *do* need to reach icons.pokenini.local though (for
//     images) - that alias lives on pokniniweb_default (the project's own default
//     compose network, already shared with chrome/firefox), connected via:
//       docker network connect pokniniweb_default pokenini-resources --alias icons.pokenini.local
//     No sibling-project containers ride that network, so no collision there.
const HTTP_BASE_URI = 'http://www.pokenini.local:8080';
const BROWSER_BASE_URI = 'http://web:8080';
const SELENIUM_HOST = 'http://chrome:4444/wd/hub';
const OUTPUT_DIR = __DIR__.'/../var/screenshots';

/** @var array<string, array{0: int, 1: int}> */
const VIEWPORTS = [
    'mobile' => [375, 812],
    'tablet' => [768, 1024],
    'desktop' => [1440, 900],
];

// Chrome (real, non-headless, per selenium/standalone-chromium) enforces a minimum
// browser *window* width somewhere around 500px - manage()->window()->setSize(375, ...)
// silently gets clamped, so every "mobile" screenshot was actually ~500px wide.
// mobileEmulation decouples the rendered viewport from the window size (the same
// mechanism as Chrome DevTools' device toolbar), so it's the only way to get a real
// 375px render. It has to be set as a session capability, not changed after the fact,
// which is why the mobile viewport gets its own Client instance below.
const MOBILE_EMULATION = [
    'deviceMetrics' => ['width' => 375, 'height' => 812, 'pixelRatio' => 3],
    'userAgent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 Mobile/15E148 Safari/604.1',
];

/** Role => fake-auth `t` query param (see Security/FakeAuthenticator), null = no login.
 *  "uninvited" is any `t` value FakeAuthenticator doesn't recognize (admin/collector/
 *  trainer): it authenticates the user but grants no extra role, matching real accounts
 *  that logged in without being trainer-invited yet. */
const ROLE_AUTH_PARAM = [
    'visitor' => null,
    'trainer' => 'trainer',
    'collector' => 'collector',
    'admin' => 'admin',
    'uninvited' => 'uninvited',
];

/** Roles that hold at least ROLE_TRAINER (config/packages/security.yaml), i.e. can
 *  reach /trainer, /album/dex and /election - "uninvited" can't, it only has the
 *  bare ROLE_USER granted to any authenticated fake session. */
const TRAINER_ROLES = ['trainer', 'collector', 'admin'];

/** Page slug => expected HTTP status. Anything not listed here defaults to 200.
 *  Slug is "error-404", not "404": a purely-numeric string array key gets coerced to
 *  an int key by PHP, and the [...a, ...b] merge used below renumbers int keys
 *  sequentially - so a '404' key would silently become 0 after the merge and never
 *  match here. */
const EXPECTED_STATUS = [
    'error-404' => 404, // Symfony's error-preview route (see staticPagesFor) responds 404 by design
];

/** Suppresses the tarteaucitron consent banner for a Panther session, same trick as
 *  AbstractBrowserTestCase::loginUser() - must run after at least one navigation
 *  to the site so the cookie is set on the right origin. */
function suppressCookieBanner(Client $client): void
{
    $client->executeScript("document.cookie = 'tarteaucitron=!matomocloud=false; path=/';");
}

/** Removes the Symfony web debug toolbar, which only renders in this dev vhost
 *  (the test vhost forces APP_ENV=test, which disables it) and would otherwise
 *  sit fixed at the bottom of every screenshot. Must be called after each page
 *  load, since the toolbar is re-injected fresh on every navigation. */
function hideDebugToolbar(Client $client): void
{
    $client->executeScript("document.querySelector('.sf-toolbar')?.remove();");
}

/** Navigates home, suppresses the cookie banner for the rest of this session, then
 *  logs in if $authParam is set. Needed once per Client instance (cookies/session
 *  aren't shared between the desktop/tablet client and the mobile-emulation one). */
function bootstrapSession(Client $client, string $locale, ?string $authParam): void
{
    $client->request('GET', "/$locale");
    suppressCookieBanner($client);

    if (null !== $authParam) {
        $client->request('GET', "/$locale/connect/f/c?t=$authParam");
    }
}

/**
 * @return array{locale: string, roles: list<string>}
 */
function parseArgs(array $argv): array
{
    $locale = 'fr';
    $roles = array_keys(ROLE_AUTH_PARAM);

    foreach (array_slice($argv, 1) as $arg) {
        if (str_starts_with($arg, '--locale=')) {
            $locale = substr($arg, \strlen('--locale='));
        }
        if (str_starts_with($arg, '--roles=')) {
            $roles = explode(',', substr($arg, \strlen('--roles=')));
        }
    }

    return ['locale' => $locale, 'roles' => $roles];
}

/**
 * Pages that always exist, independent of fixture data.
 *
 * @return array<string, string> page slug => absolute path
 */
function staticPagesFor(string $role, string $locale): array
{
    $pages = [
        'home' => "/$locale",
        'connect' => "/$locale/connect",
        'legals' => "/$locale/legals",
        'policy' => "/$locale/policy",
        'cookies' => "/$locale/cookies",
        // Not a made-up route: with APP_DEBUG on (the dev vhost default), a real 404
        // shows Symfony's raw exception page, not the custom error404.html.twig - only
        // debug=false renders it. Symfony's own preview route (config/routes/framework.yaml,
        // when@dev only) renders the real custom template regardless of debug, so this
        // shows what a production 404 actually looks like without touching APP_DEBUG
        // (which would also silence the dev toolbar/profiler for real day-to-day use).
        'error-404' => "/$locale/_error/404",
    ];

    // /outerroom is ROLE_USER (any authenticated session): trainer/collector/admin
    // are already validated and get redirected away from it, but "uninvited" is the
    // one role that actually sees this page's real content (the invite-pending
    // waiting room), so it's worth capturing there specifically.
    if ('visitor' !== $role) {
        $pages['outerroom'] = "/$locale/outerroom";
    }

    // /trainer, /album/dex and /election require ROLE_TRAINER - "uninvited" would
    // just get a 403 for these, so don't even try.
    if (\in_array($role, TRAINER_ROLES, true)) {
        $pages['trainer'] = "/$locale/trainer";
        $pages['album-dex-list'] = "/$locale/album/dex";
        $pages['election-dex-list'] = "/$locale/election/dex";
    }

    if ('admin' === $role) {
        $pages['admin-actions'] = "/$locale/istration/actions";
        $pages['admin-reports'] = "/$locale/istration/reports";
    }

    return $pages;
}

/**
 * Visits the album/election dex-list pages and follows their links to find
 * real detail-page slugs, instead of hardcoding dex slugs from today's
 * fixtures that will go stale as data changes.
 *
 * @return array<string, string> page slug => absolute path
 */
function discoverDetailPages(HttpBrowser $browser, string $role, string $locale): array
{
    if (!\in_array($role, TRAINER_ROLES, true)) {
        return [];
    }

    $pages = [];

    $browser->request('GET', HTTP_BASE_URI."/$locale/album/dex");
    foreach ($browser->getCrawler()->filter("a[href^=\"/$locale/album/\"]")->extract(['href']) as $href) {
        if (!str_contains($href, '/album/dex')) {
            $pages['album-'.basename((string) parse_url($href, PHP_URL_PATH))] = $href;
        }
    }

    $browser->request('GET', HTTP_BASE_URI."/$locale/election/dex");
    foreach ($browser->getCrawler()->filter("a[href^=\"/$locale/election/\"]")->extract(['href']) as $href) {
        if (!str_contains($href, '/election/dex')) {
            $pages['election-'.basename((string) parse_url($href, PHP_URL_PATH))] = $href;
        }
    }

    return $pages;
}

/**
 * Drops any page whose real HTTP status doesn't match what's expected
 * (200 by default, see EXPECTED_STATUS) - fixture data isn't always
 * internally consistent, so list pages can link to detail pages that 404.
 *
 * Uses a raw HEAD request instead of $browser->request('GET', ...): some
 * dexes render one full detail modal per Pokémon with no pagination, so a
 * full dex can be 10-15MB of HTML - AbstractBrowser::request() always
 * parses the body into a DOM crawler even when nothing here reads it,
 * which reliably exhausted PHP's memory_limit on those pages. A HEAD
 * request never even downloads the body.
 *
 * @param array<string, string> $pages page slug => absolute path
 *
 * @return array<string, string> the subset of $pages that responded as expected
 */
function filterReachablePages(HttpBrowser $browser, HttpClientInterface $httpClient, array $pages): array
{
    $reachable = [];

    foreach ($pages as $pageSlug => $path) {
        $url = str_starts_with($path, 'http') ? $path : HTTP_BASE_URI.$path;
        $cookies = $browser->getCookieJar()->allRawValues($url);
        $cookieHeader = implode('; ', array_map(
            static fn (string $name, string $value): string => "$name=$value",
            array_keys($cookies),
            $cookies,
        ));

        $response = $httpClient->request('HEAD', $url, [
            'headers' => '' === $cookieHeader ? [] : ['Cookie' => $cookieHeader],
        ]);
        $status = $response->getStatusCode();
        $expected = EXPECTED_STATUS[$pageSlug] ?? 200;

        if ($status === $expected) {
            $reachable[$pageSlug] = $path;
        } else {
            fwrite(\STDERR, "Skipping \"$pageSlug\" ($path): expected $expected, got $status.".\PHP_EOL);
        }
    }

    return $reachable;
}

['locale' => $locale, 'roles' => $roles] = parseArgs($argv);

$filesystem = new Filesystem();
$filesystem->remove(OUTPUT_DIR);
$filesystem->mkdir(OUTPUT_DIR);

$capabilities = DesiredCapabilities::chrome();
$capabilities->setCapability('acceptInsecureCerts', true);

$mobileCapabilities = DesiredCapabilities::chrome();
$mobileCapabilities->setCapability('acceptInsecureCerts', true);
$mobileCapabilities->setCapability('goog:chromeOptions', ['mobileEmulation' => MOBILE_EMULATION]);

$cookieBannerCaptured = false;

foreach ($roles as $role) {
    if (!\array_key_exists($role, ROLE_AUTH_PARAM)) {
        fwrite(\STDERR, "Unknown role \"$role\", skipping. Valid roles: ".implode(', ', array_keys(ROLE_AUTH_PARAM)).\PHP_EOL);

        continue;
    }

    echo "== $role ==".\PHP_EOL;
    $authParam = ROLE_AUTH_PARAM[$role];

    // --- Pass 1: plain HTTP, authenticate, discover links, drop dead ones ---
    $httpClient = HttpClient::create();
    $httpBrowser = new HttpBrowser($httpClient);
    if (null !== $authParam) {
        $httpBrowser->request('GET', HTTP_BASE_URI."/$locale/connect/f/c?t=$authParam");
    }

    $candidatePages = [...staticPagesFor($role, $locale), ...discoverDetailPages($httpBrowser, $role, $locale)];
    $pages = filterReachablePages($httpBrowser, $httpClient, $candidatePages);

    // --- Pass 2: real browser, screenshot every surviving page ---
    // Two clients: $client covers tablet/desktop via window resizing, $mobileClient
    // is a separate session with mobileEmulation baked in at creation time (see
    // MOBILE_EMULATION above) since that capability can't be toggled mid-session -
    // and run sequentially, not concurrently: selenium/standalone-chromium hosts a
    // single session at a time, so opening $mobileClient while $client is still open
    // just hangs the second createSeleniumClient() call until it times out.
    $client = Client::createSeleniumClient(SELENIUM_HOST, $capabilities, BROWSER_BASE_URI);

    if (!$cookieBannerCaptured) {
        // Capture the tarteaucitron banner exactly once across the whole run - it's
        // identical on every page, no need to have it clutter every screenshot.
        $client->manage()->window()->setSize(new WebDriverDimension(...VIEWPORTS['desktop']));
        $client->request('GET', "/$locale");
        usleep(300_000);
        hideDebugToolbar($client);
        $client->takeScreenshot(OUTPUT_DIR.'/_cookie-banner.png');
        $cookieBannerCaptured = true;
    }

    bootstrapSession($client, $locale, $authParam);

    foreach ($pages as $pageSlug => $path) {
        foreach (['tablet', 'desktop'] as $viewportName) {
            [$width, $height] = VIEWPORTS[$viewportName];
            $client->manage()->window()->setSize(new WebDriverDimension($width, $height));
            $client->request('GET', $path); // Panther prepends BROWSER_BASE_URI for relative paths.
            // Let Bootstrap/JS finish initializing (tooltips, modals wiring, ...) before capturing.
            usleep(300_000);
            hideDebugToolbar($client);

            $filename = \sprintf('%s/%s__%s__%s.png', OUTPUT_DIR, $role, $pageSlug, $viewportName);
            $client->takeScreenshot($filename);
            echo $filename.\PHP_EOL;
        }
    }

    $client->quit();

    // --- Pass 2b: mobile-emulation browser, its own sequential session ---
    $mobileClient = Client::createSeleniumClient(SELENIUM_HOST, $mobileCapabilities, BROWSER_BASE_URI);
    bootstrapSession($mobileClient, $locale, $authParam);

    foreach ($pages as $pageSlug => $path) {
        $mobileClient->request('GET', $path);
        usleep(300_000);
        hideDebugToolbar($mobileClient);

        $filename = \sprintf('%s/%s__%s__mobile.png', OUTPUT_DIR, $role, $pageSlug);
        $mobileClient->takeScreenshot($filename);
        echo $filename.\PHP_EOL;
    }

    $mobileClient->quit();
}

echo 'Done. Screenshots in '.OUTPUT_DIR.\PHP_EOL;
