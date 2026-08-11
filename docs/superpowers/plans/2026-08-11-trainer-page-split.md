# Trainer Page Split Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Replace the single `/trainer` page's in-page Bootstrap-JS tab switcher (personal data / links / logout) with three separate server-rendered pages (customization, links, personal data), and move the logout link out of the Trainer page into the persistent bottom nav bar.

**Architecture:** One Symfony controller per page (existing project convention), following the same "page-level `nav-tabs` as plain links" pattern already used for the Admin Actions/Reports split. No new services; existing services are relocated to the controller that now needs them. Logout becomes a nav-bar `<li>`, gated on `app.user` instead of `ROLE_TRAINER`.

**Tech Stack:** Symfony 8 / PHP ≥ 8.5, Twig, Bootstrap 5.3 (CSS only for this feature — no new JS), PHPUnit `WebTestCase` + Moco fixtures for integration tests.

## Global Constraints

- Docker-only toolchain — every command in this plan runs via `docker compose exec php ...` or `make ...` from inside `pokenini-web/`, never directly on the host.
- `declare(strict_types=1);` at the top of every PHP file.
- Controller classes are `final`.
- Test classes are `final`, carry `/** @internal */`, and `#[CoversClass(TargetClass::class)]`.
- Layered architecture enforced by Deptrac: controllers may depend on `Service/`, not `Service/Api/` or `Service/Back/`'s HTTP internals directly (existing services already respect this; nothing new is added here).
- `make quality` and `make measures` (100% coverage, 100% Mutation Score Index, PHPStan level 9, Psalm strict) must be green before pushing — verified in the final task.
- Integration tests use Moco fixtures (`tests/resources/moco/Back/`), never HTTP-client mocks. This plan reuses existing fixtures only; no fixture changes are needed anywhere (verified per-task below).

---

## File Map

| File | Change |
|---|---|
| `templates/_nav.html.twig` | Modify — add logout `<li>` |
| `templates/Trainer/_section.html.twig` | Modify (drop `logout`) in Task 1, then delete in Task 4 |
| `templates/Trainer/Section/_logout.html.twig` | Delete (Task 1) |
| `tests/src/Common/Traits/TestNavTrait.php` | Modify — add `assertLogoutNavBar()` |
| `tests/src/Integration/Controller/Trainer/TrainerPageTest.php` | Modify across Tasks 1, 4, 5 |
| `translations/messages+intl-icu.fr.yaml` / `.en.yaml` | Modify — drop `trainer.logout.title` (Task 1), reword `trainer.welcome` (Task 4) |
| `src/Controller/TrainerPersonnalDataController.php` | Create (Task 2) |
| `templates/Trainer/personnal_data.html.twig` | Create (Task 2) |
| `tests/src/Integration/Controller/Trainer/TrainerPersonnalDataPageTest.php` | Create (Task 2) |
| `src/Controller/TrainerLinksController.php` | Create (Task 3) |
| `templates/Trainer/links.html.twig` | Create (Task 3) |
| `tests/src/Integration/Controller/Trainer/TrainerLinksPageTest.php` | Create (Task 3) |
| `src/Controller/TrainerIndexController.php` | Modify — drop links-tree fetch (Task 4) |
| `templates/Trainer/index.html.twig` | Modify — drop tab shell + JS (Task 4), add tabs include (Task 5) |
| `public/js/trainer_tabs.js` | Delete (Task 4) |
| `templates/Trainer/_tabs.html.twig` | Create (Task 5) |
| `templates/Trainer/links.html.twig`, `personnal_data.html.twig` | Modify — add tabs include (Task 5) |
| `templates/Album/_offcanvas.html.twig` | Modify — repoint deep link (Task 6) |
| `tests/src/Integration/Controller/Album/Display/OffcanvasTest.php` | Modify — assert new link (Task 6) |

Not touched anywhere in this plan: `templates/Trainer/Section/_dex.html.twig`, `_dex_filters.html.twig`, `_links.html.twig`, `_personnal_data.html.twig` (all three section partials are reused unchanged by their new page templates), `TrainerUpsertController`, `TrainerDexLinkController`, `GetTrainerDexListService`, `GetTrainerDexLinksTreeService`, `security.yaml`.

---

### Task 1: Move the logout link into the bottom nav bar

**Files:**
- Modify: `tests/src/Common/Traits/TestNavTrait.php`
- Modify: `tests/src/Integration/Controller/Trainer/TrainerPageTest.php`
- Modify: `templates/_nav.html.twig`
- Modify: `templates/Trainer/_section.html.twig`
- Delete: `templates/Trainer/Section/_logout.html.twig`
- Modify: `translations/messages+intl-icu.fr.yaml`, `translations/messages+intl-icu.en.yaml`

**Interfaces:**
- Produces: `TestNavTrait::assertLogoutNavBar(Crawler $crawler): void` — asserts exactly one `.navbar-nav .logout-link` whose `<a>` href contains `/connect/logout`. Used by this task's test and by Tasks 4 and 5's test files.

- [ ] **Step 1: Write the failing assertions**

Add to `tests/src/Common/Traits/TestNavTrait.php`, right after `assertAdminAlbumNavBar`:

```php
    public function assertLogoutNavBar(Crawler $crawler): void
    {
        $this->assertCountFilter($crawler, 1, '.navbar-nav .logout-link');
        $this->assertStringContainsString(
            '/connect/logout',
            $crawler->filter('.navbar-nav .logout-link a')->attr('href') ?? ''
        );
    }
```

In `tests/src/Integration/Controller/Trainer/TrainerPageTest.php`, replace each of the three occurrences of:

```php
        $this->assertStringContainsString(
            '/connect/logout',
            $crawler->filter('#section-logout a')->attr('href') ?? ''
        );
```

with:

```php
        $this->assertLogoutNavBar($crawler);
```

(one occurrence each in `trainerPage()`, `collectorPage()`, `adminTrainerPage()`).

- [ ] **Step 2: Run the tests to verify they fail**

Run: `docker compose exec php php vendor/bin/phpunit tests/src/Integration/Controller/Trainer/TrainerPageTest.php`
Expected: FAIL — `assertLogoutNavBar` finds 0 `.navbar-nav .logout-link` elements (the nav bar doesn't have one yet).

- [ ] **Step 3: Add the logout link to the nav bar**

In `templates/_nav.html.twig`, insert a new `<li>` as the last child of `<ul class="navbar-nav">`, right after the closing `{% endif %}` of the admin-link block (before `</ul>`):

```twig
                {% if app.user %}
                <li class="nav-item logout-link ms-auto">
                    <a
                        class="nav-link"
                        href="{{ path('app_connect_logout') }}"
                    >
                        <i class="bi bi-box-arrow-right"></i>
                        {{ 'logout'|trans }}
                    </a>
                </li>
                {% endif %}
```

- [ ] **Step 4: Remove the logout tab from the Trainer page**

In `templates/Trainer/_section.html.twig`, change:

```twig
{% set sections = [
    'personnal_data',
    'links',
    'logout',
] %}
```

to:

```twig
{% set sections = [
    'personnal_data',
    'links',
] %}
```

Delete `templates/Trainer/Section/_logout.html.twig`.

- [ ] **Step 5: Drop the now-unused translation key**

In `translations/messages+intl-icu.fr.yaml`, remove from the `trainer:` block:

```yaml
  logout:
    title: "Session"
```

In `translations/messages+intl-icu.en.yaml`, remove the same two lines from its `trainer:` block.

- [ ] **Step 6: Run the tests to verify they pass**

Run: `docker compose exec php php vendor/bin/phpunit tests/src/Integration/Controller/Trainer/TrainerPageTest.php`
Expected: PASS (all three tests).

- [ ] **Step 7: Commit**

```bash
git add templates/_nav.html.twig templates/Trainer/_section.html.twig \
  translations/messages+intl-icu.fr.yaml translations/messages+intl-icu.en.yaml \
  tests/src/Common/Traits/TestNavTrait.php tests/src/Integration/Controller/Trainer/TrainerPageTest.php
git rm templates/Trainer/Section/_logout.html.twig
git commit -m "feat: move trainer logout link into the bottom nav bar"
```

---

### Task 2: Create the personal data page

**Files:**
- Create: `src/Controller/TrainerPersonnalDataController.php`
- Create: `templates/Trainer/personnal_data.html.twig`
- Test: `tests/src/Integration/Controller/Trainer/TrainerPersonnalDataPageTest.php`

**Interfaces:**
- Consumes: `templates/Trainer/Section/_personnal_data.html.twig` (existing, unchanged — reads `app.user.id` / `app.user.providerName` directly, no controller variables needed).
- Produces: route `app_trainerpersonnaldata_index`, path `/trainer/personnal_data`, `#[IsGranted('ROLE_TRAINER')]`. Used by Task 5 (tabs) and referenced in the file map only — nothing else in this plan links to it directly.

- [ ] **Step 1: Write the failing test**

Create `tests/src/Integration/Controller/Trainer/TrainerPersonnalDataPageTest.php`:

```php
<?php

declare(strict_types=1);

namespace App\Tests\Integration\Controller\Trainer;

use App\Controller\TrainerPersonnalDataController;
use App\Tests\Utils\GetUserToken;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @internal
 */
#[CoversClass(TrainerPersonnalDataController::class)]
#[Group('api-mocked-testing')]
final class TrainerPersonnalDataPageTest extends WebTestCase
{
    #[Test]
    public function personnalDataPage(): void
    {
        $client = self::createClient();

        $user = GetUserToken::getFakeUserToken();
        $user->addTrainerRole();
        $client->loginUser($user, 'web');

        $crawler = $client->request('GET', '/fr/trainer/personnal_data');

        $this->assertResponseStatusCodeSame(200);

        $this->assertSame(
            'Pokénini Ton espace de dresseur',
            $crawler->filter('title')->text()
        );

        $this->assertSame(
            'Ton espace de dresseur',
            $crawler->filter('h1')->text()
        );

        $this->assertCount(1, $crawler->filter('h1'));
        $this->assertCount(2, $crawler->filter('table thead th'));
        $this->assertCount(2, $crawler->filter('table tbody tr'));
        $this->assertEquals('Identifiant 789465465489', $crawler->filter('table tbody tr')->eq(0)->text());
        $this->assertEquals("Service d'identification TestProvider", $crawler->filter('table tbody tr')->eq(1)->text());
    }

    #[Test]
    public function personnalDataPageNotAllowed(): void
    {
        $client = self::createClient();

        $user = GetUserToken::getFakeUserToken();
        $client->loginUser($user, 'web');

        $client->request('GET', '/fr/trainer/personnal_data');

        $this->assertResponseStatusCodeSame(403);
    }
}
```

- [ ] **Step 2: Run the test to verify it fails**

Run: `docker compose exec php php vendor/bin/phpunit tests/src/Integration/Controller/Trainer/TrainerPersonnalDataPageTest.php`
Expected: FAIL — `App\Controller\TrainerPersonnalDataController` doesn't exist yet (class-not-found error).

- [ ] **Step 3: Write the controller and template**

Create `src/Controller/TrainerPersonnalDataController.php`:

```php
<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/trainer')]
final class TrainerPersonnalDataController extends AbstractController
{
    #[Route('/personnal_data', methods: ['GET'])]
    #[IsGranted('ROLE_TRAINER')]
    public function index(): Response
    {
        return $this->render('Trainer/personnal_data.html.twig');
    }
}
```

Create `templates/Trainer/personnal_data.html.twig`:

```twig
{% extends 'base.html.twig' %}
{% use '_nav.html.twig' %}

{% block title %}Pokénini {{ 'title.trainer'|trans }}{% endblock title %}

{% block container %}
  <div class="row">
    <div class="col-md-10 mx-auto text-center row">
      <h1>{{ 'title.trainer'|trans }}</h1>
    </div>
  </div>

  <div class="row">
    <div class="col-md-12 mx-auto row">
      {{ include('Trainer/Section/_personnal_data.html.twig') }}
    </div>
  </div>
{% endblock container %}
```

- [ ] **Step 4: Run the test to verify it passes**

Run: `docker compose exec php php vendor/bin/phpunit tests/src/Integration/Controller/Trainer/TrainerPersonnalDataPageTest.php`
Expected: PASS (both tests).

- [ ] **Step 5: Commit**

```bash
git add src/Controller/TrainerPersonnalDataController.php \
  templates/Trainer/personnal_data.html.twig \
  tests/src/Integration/Controller/Trainer/TrainerPersonnalDataPageTest.php
git commit -m "feat: add standalone trainer personal data page"
```

---

### Task 3: Create the album links page

**Files:**
- Create: `src/Controller/TrainerLinksController.php`
- Create: `templates/Trainer/links.html.twig`
- Test: `tests/src/Integration/Controller/Trainer/TrainerLinksPageTest.php`

**Interfaces:**
- Consumes: `App\Service\GetTrainerDexLinksTreeService::getTree(): TrainerDexLinksTree` (existing, unchanged). `templates/Trainer/Section/_links.html.twig` (existing, unchanged — needs `linksTree` and `locale` in context, plus the `dexBannerUrl` global already registered in `config/packages/twig.yaml`).
- Produces: route `app_trainerlinks_index`, path `/trainer/links`, `#[IsGranted('ROLE_TRAINER')]`. Used by Task 5 (tabs) and Task 6 (offcanvas deep link).

There is no Moco fixture under `tests/resources/moco/Back/` matching `/album_link/{dexSlug}` (verified — none exists today, and the current `/trainer` page already exercises this exact call path without one). `TrainerDexLinkService::list()` throws `HttpExceptionInterface` on the unmatched request, which `GetTrainerDexLinksTreeService::listLinksFor()` catches and treats as "no links for this dex" — so the aggregated tree is empty in every test scenario and the page renders its "empty" copy. No new fixtures are needed.

- [ ] **Step 1: Write the failing test**

Create `tests/src/Integration/Controller/Trainer/TrainerLinksPageTest.php`:

```php
<?php

declare(strict_types=1);

namespace App\Tests\Integration\Controller\Trainer;

use App\Controller\TrainerLinksController;
use App\Tests\Utils\GetUserToken;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @internal
 */
#[CoversClass(TrainerLinksController::class)]
#[Group('api-mocked-testing')]
final class TrainerLinksPageTest extends WebTestCase
{
    #[Test]
    public function linksPage(): void
    {
        $client = self::createClient();

        $user = GetUserToken::getFakeUserToken();
        $user->addTrainerRole();
        $client->loginUser($user, 'web');

        $crawler = $client->request('GET', '/fr/trainer/links');

        $this->assertResponseStatusCodeSame(200);

        $this->assertSame(
            'Pokénini Ton espace de dresseur',
            $crawler->filter('title')->text()
        );

        $this->assertSame(
            'Ton espace de dresseur',
            $crawler->filter('h1')->text()
        );

        $this->assertCount(1, $crawler->filter('h1'));

        $mainText = $crawler->filter('#main-container')->text();

        $this->assertStringContainsString(
            'Voici tous les liens que tu as créés entre tes dex.',
            $mainText
        );
        $this->assertStringContainsString(
            "Tu n'as pas encore créé de lien entre tes dex.",
            $mainText
        );
        $this->assertCount(0, $crawler->filter('.dex-links-tree'));
    }

    #[Test]
    public function linksPageNotAllowed(): void
    {
        $client = self::createClient();

        $user = GetUserToken::getFakeUserToken();
        $client->loginUser($user, 'web');

        $client->request('GET', '/fr/trainer/links');

        $this->assertResponseStatusCodeSame(403);
    }
}
```

- [ ] **Step 2: Run the test to verify it fails**

Run: `docker compose exec php php vendor/bin/phpunit tests/src/Integration/Controller/Trainer/TrainerLinksPageTest.php`
Expected: FAIL — `App\Controller\TrainerLinksController` doesn't exist yet.

- [ ] **Step 3: Write the controller and template**

Create `src/Controller/TrainerLinksController.php`:

```php
<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\GetTrainerDexLinksTreeService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/trainer')]
final class TrainerLinksController extends AbstractController
{
    public function __construct(
        private readonly GetTrainerDexLinksTreeService $getTrainerDexLinksTreeService,
    ) {}

    #[Route('/links', methods: ['GET'])]
    #[IsGranted('ROLE_TRAINER')]
    public function index(): Response
    {
        return $this->render(
            'Trainer/links.html.twig',
            [
                'linksTree' => $this->getTrainerDexLinksTreeService->getTree(),
            ]
        );
    }
}
```

Create `templates/Trainer/links.html.twig`:

```twig
{% set locale = app.request.locale %}

{% extends 'base.html.twig' %}
{% use '_nav.html.twig' %}

{% block title %}Pokénini {{ 'title.trainer'|trans }}{% endblock title %}

{% block container %}
  <div class="row">
    <div class="col-md-10 mx-auto text-center row">
      <h1>{{ 'title.trainer'|trans }}</h1>
    </div>
  </div>

  <div class="row">
    <div class="col-md-12 mx-auto row">
      {{ include('Trainer/Section/_links.html.twig') }}
    </div>
  </div>
{% endblock container %}
```

- [ ] **Step 4: Run the test to verify it passes**

Run: `docker compose exec php php vendor/bin/phpunit tests/src/Integration/Controller/Trainer/TrainerLinksPageTest.php`
Expected: PASS (both tests).

- [ ] **Step 5: Commit**

```bash
git add src/Controller/TrainerLinksController.php \
  templates/Trainer/links.html.twig \
  tests/src/Integration/Controller/Trainer/TrainerLinksPageTest.php
git commit -m "feat: add standalone trainer album links page"
```

---

### Task 4: Slim the customization page down to just customization

**Files:**
- Modify: `src/Controller/TrainerIndexController.php`
- Modify: `templates/Trainer/index.html.twig`
- Delete: `templates/Trainer/_section.html.twig`
- Delete: `public/js/trainer_tabs.js`
- Modify: `translations/messages+intl-icu.fr.yaml`, `translations/messages+intl-icu.en.yaml`
- Modify: `tests/src/Integration/Controller/Trainer/TrainerPageTest.php`

**Interfaces:**
- Consumes: nothing new.
- Produces: `TrainerIndexController` no longer depends on `GetTrainerDexLinksTreeService` — confirms that dependency now lives solely in `TrainerLinksController` (Task 3).

- [ ] **Step 1: Write the failing test**

Replace the full contents of `tests/src/Integration/Controller/Trainer/TrainerPageTest.php` with:

```php
<?php

declare(strict_types=1);

namespace App\Tests\Integration\Controller\Trainer;

use App\Controller\TrainerIndexController;
use App\Tests\Common\Traits\TestNavTrait;
use App\Tests\Utils\GetUserToken;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Crawler;

/**
 * @internal
 */
#[CoversClass(TrainerIndexController::class)]
#[Group('api-mocked-testing')]
final class TrainerPageTest extends WebTestCase
{
    use TestNavTrait;

    #[Test]
    public function trainerPage(): void
    {
        $client = self::createClient();

        $user = GetUserToken::getFakeUserToken();
        $user->addTrainerRole();
        $client->loginUser($user, 'web');

        $crawler = $client->request('GET', '/fr/trainer');

        $this->assertResponseStatusCodeSame(200);

        $this->assertSame(
            'Pokénini Ton espace de dresseur',
            $crawler->filter('title')->text()
        );

        $this->assertSame(
            'Ton espace de dresseur',
            $crawler->filter('h1')->text()
        );

        $this->assertCountFilter($crawler, 1, 'h1');

        $this->assertStringContainsString(
            'Tu peux y personnaliser tes albums, consulter les liens entre tes dex et tes données personnelles.',
            $crawler->filter('#main-container')->text()
        );

        $this->assertCustomizeAlbumSection($crawler, false, false, 5);

        $this->assertLogoutNavBar($crawler);

        $this->assertCount(0, $crawler->filter('.navbar-link'));

        $this->assertCountFilter($crawler, 1, '.dex_is_shiny');
        $this->assertCountFilter($crawler, 2, '.dex_is_premium');
        $this->assertCountFilter($crawler, 0, '.dex_not_is_released');
        $this->assertCountFilter($crawler, 1, '.dex_is_custom');
    }

    #[Test]
    public function collectorPage(): void
    {
        $client = self::createClient();

        $user = GetUserToken::getFakeUserToken();
        $user->addTrainerRole();
        $user->addCollectorRole();
        $client->loginUser($user, 'web');

        $crawler = $client->request('GET', '/fr/trainer');

        $this->assertResponseStatusCodeSame(200);

        $this->assertSame(
            'Pokénini Ton espace de dresseur',
            $crawler->filter('title')->text()
        );

        $this->assertSame(
            'Ton espace de dresseur',
            $crawler->filter('h1')->text()
        );

        $this->assertCountFilter($crawler, 1, 'h1');

        $this->assertCustomizeAlbumSection($crawler, false, true, 5);

        $this->assertLogoutNavBar($crawler);

        $this->assertCount(0, $crawler->filter('.navbar-link'));

        $this->assertCountFilter($crawler, 1, '.dex_is_shiny');
        $this->assertCountFilter($crawler, 2, '.dex_is_premium');
        $this->assertCountFilter($crawler, 0, '.dex_not_is_released');
        $this->assertCountFilter($crawler, 1, '.dex_is_custom');
    }

    #[Test]
    public function adminTrainerPage(): void
    {
        $client = self::createClient();

        $user = GetUserToken::getFakeUserToken('8764532', 'TestAdminProvider');
        $user->addTrainerRole();
        $user->addCollectorRole();
        $user->addAdminRole();
        $client->loginUser($user, 'web');

        $crawler = $client->request('GET', '/fr/trainer');

        $this->assertResponseStatusCodeSame(200);

        $this->assertSame(
            'Pokénini Ton espace de dresseur',
            $crawler->filter('title')->text()
        );

        $this->assertSame(
            'Ton espace de dresseur',
            $crawler->filter('h1')->text()
        );

        $this->assertCountFilter($crawler, 1, 'h1');

        $this->assertCustomizeAlbumSection($crawler, true, true, 0);

        $this->assertLogoutNavBar($crawler);

        $this->assertCount(0, $crawler->filter('.navbar-link'));

        $this->assertCountFilter($crawler, 2, '.dex_is_shiny');
        $this->assertCountFilter($crawler, 3, '.dex_is_premium');
        $this->assertCountFilter($crawler, 2, '.dex_not_is_released');
        $this->assertCountFilter($crawler, 0, '.dex_is_custom');
    }

    #[Test]
    public function trainerPageNotAllowed(): void
    {
        $client = self::createClient();

        $user = GetUserToken::getFakeUserToken();
        $client->loginUser($user, 'web');

        $client->request('GET', '/fr/trainer');

        $this->assertResponseStatusCodeSame(403);
    }

    private function assertCustomizeAlbumSection(Crawler $crawler, bool $isAdmin, bool $isCollector, int $reportCount): void
    {
        $this->assertCountFilter($crawler, 1, 'form#dexFilters');

        $this->assertCountFilter($crawler, 1, 'form#dexFilters', 0, '#filter-privacy');
        $this->assertCountFilter($crawler, 3, 'form#dexFilters #filter-privacy', 0, 'option');
        $this->assertSelectedOptions($crawler, 'select#filter-privacy', ['']);

        $this->assertCountFilter($crawler, 1, 'form#dexFilters', 0, '#filter-homepaged');
        $this->assertCountFilter($crawler, 3, 'form#dexFilters #filter-homepaged', 0, 'option');
        $this->assertSelectedOptions($crawler, 'select#filter-homepaged', ['']);

        $this->assertCountFilter($crawler, $isAdmin ? 1 : 0, 'form#dexFilters', 0, '#filter-released');
        if ($isAdmin) {
            $this->assertCountFilter($crawler, 3, 'form#dexFilters #filter-released', 0, 'option');
            $this->assertSelectedOptions($crawler, 'select#filter-released', ['']);
        }

        $this->assertCountFilter($crawler, $isCollector ? 1 : 0, 'form#dexFilters', 0, '#filter-premium');
        if ($isCollector) {
            $this->assertCountFilter($crawler, 3, 'form#dexFilters #filter-premium', 0, 'option');
            $this->assertSelectedOptions($crawler, 'select#filter-premium', ['']);
        }

        $this->assertCountFilter($crawler, 1, 'form#dexFilters', 0, '#filter-shiny');
        $this->assertCountFilter($crawler, 3, 'form#dexFilters #filter-shiny', 0, 'option');
        $this->assertSelectedOptions($crawler, 'select#filter-shiny', ['']);

        $this->assertCountFilter($crawler, 21, '.trainer-dex-item');
        $this->assertCountFilter($crawler, 21, '.trainer-dex-item img');
        $this->assertCountFilter($crawler, 21, '.trainer-dex-item a');
        $this->assertCountFilter($crawler, 21, '.trainer-dex-item h5');
        $this->assertCountFilter($crawler, 0, '.trainer-dex-item h6');
        $this->assertCountFilter($crawler, 42, '.trainer-dex-item input[type="checkbox"]');
        $this->assertCountFilter($crawler, $reportCount, '.trainer-dex-item .progress');

        $this->assertEmpty($crawler->filter('#goldsilvercrystal-is_private')->attr('checked'));
        $this->assertEmpty($crawler->filter('#goldsilvercrystal-is_on_home')->attr('checked'));

        $this->assertNull($crawler->filter('#home-is_private')->attr('checked'));
        $this->assertEmpty($crawler->filter('#home-is_on_home')->attr('checked'));

        $this->assertStringContainsString(
            'https://icon.pokenini.fr/dex/',
            (string) $crawler->filter('.trainer-dex-item img')->eq(0)->attr('src')
        );
    }
}
```

(This removes the `table thead th` / `table tbody tr` / identifiant / provider assertions — that content now lives on the page tested by `TrainerPersonnalDataPageTest`. Replaces the raw `#section-logout` check with `assertLogoutNavBar()`. Adds one assertion for the reworded welcome copy.)

- [ ] **Step 2: Run the tests to verify they fail**

Run: `docker compose exec php php vendor/bin/phpunit tests/src/Integration/Controller/Trainer/TrainerPageTest.php`
Expected: FAIL — the reworded welcome text isn't in the page yet (old copy still mentions "te déconnecter"), and `table thead th` / `table tbody tr` assertions were removed so those no longer apply (the remaining failure is the welcome-copy assertion).

- [ ] **Step 3: Slim down the controller**

Replace the full contents of `src/Controller/TrainerIndexController.php` with:

```php
<?php

declare(strict_types=1);

namespace App\Controller;

use App\DTO\DexFilters;
use App\DTO\DexFiltersRequest;
use App\ResponseObject\Album\DexListItem;
use App\Service\Back\GetTrainerDexListService;
use App\Service\GetLabelsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/trainer')]
final class TrainerIndexController extends AbstractController
{
    public function __construct(
        private readonly GetTrainerDexListService $getDexService,
        private readonly GetLabelsService $getLabelsService,
    ) {}

    #[Route('')]
    #[IsGranted('ROLE_TRAINER')]
    public function index(Request $request): Response
    {
        $trainerDex = $this->getDexService->get();

        $filters = DexFiltersRequest::dexFiltersFromRequest($request);

        $filteredTrainerDex = $this->filtersTrainerDex($trainerDex, $filters);

        return $this->render(
            'Trainer/index.html.twig',
            [
                'trainerDex' => $filteredTrainerDex,
                'filters' => $filters,
                'catchStates' => $this->getLabelsService->getCatchStates(),
            ]
        );
    }

    /**
     * @param DexListItem[] $trainerDex
     *
     * @return DexListItem[]
     */
    private function filtersTrainerDex(array $trainerDex, DexFilters $filters): array
    {
        $dex = $trainerDex;

        if (null !== $filters->privacy->value) {
            $dex = array_filter(
                $dex,
                function (DexListItem $item) use ($filters) {
                    return $filters->privacy->value === $item->getFlags()->isPrivate();
                }
            );
        }

        if (null !== $filters->homepaged->value) {
            $dex = array_filter(
                $dex,
                function (DexListItem $item) use ($filters) {
                    return $filters->homepaged->value === $item->getFlags()->isOnHome();
                }
            );
        }

        if (null !== $filters->shiny->value) {
            $dex = array_filter(
                $dex,
                function (DexListItem $item) use ($filters) {
                    return $filters->shiny->value === $item->getFlags()->isShiny();
                }
            );
        }

        if (null !== $filters->released->value) {
            $dex = array_filter(
                $dex,
                function (DexListItem $item) use ($filters) {
                    return $filters->released->value === $item->getFlags()->isReleased();
                }
            );
        }

        if (null !== $filters->premium->value) {
            $dex = array_filter(
                $dex,
                function (DexListItem $item) use ($filters) {
                    return $filters->premium->value === $item->getFlags()->isPremium();
                }
            );
        }

        return $dex;
    }
}
```

- [ ] **Step 4: Slim down the template**

Replace the full contents of `templates/Trainer/index.html.twig` with:

```twig
{% set locale = app.request.locale %}

{% extends 'base.html.twig' %}
{% use '_nav.html.twig' %}

{% block title %}Pokénini {{ 'title.trainer'|trans }}{% endblock title %}

{% block stylesheets %}
  {{ parent() }}

  <link rel="stylesheet" href="{{ asset('css/trainer.css') }}">

  <style>
  {% include 'common/_catch_state_color_tokens.html.twig' %}
  {% include 'common/_catch_state_progress_bar_styles.html.twig' %}
  </style>
{% endblock stylesheets %}

{% block container %}
  <div class="row">
    <div class="col-md-10 mx-auto text-center row">
      <h1>{{ 'title.trainer'|trans }}</h1>
    </div>
  </div>

  <div class="row">
    <div class="col-md-12 mx-auto row">

      <p class="text-secondary">{{ 'trainer.welcome'|trans|htmlNl2br }}</p>

      {{ include('Trainer/Section/_dex.html.twig') }}
    </div>
  </div>
{% endblock container %}

{% block foot_javascript %}
  {{ parent() }}
  <script src="{{ asset('js/trainer_dex.js') }}"></script>
  <script src="{{ asset('js/trainer_filters.js') }}"></script>

  <script>
    const locale = '{{ locale }}';

    watchAttributes();
    watchFilters();
  </script>
{% endblock foot_javascript %}
```

Delete `templates/Trainer/_section.html.twig` and `public/js/trainer_tabs.js`.

- [ ] **Step 5: Reword the welcome copy**

In `translations/messages+intl-icu.fr.yaml`, change the `trainer.welcome` block to:

```yaml
  welcome: |
    Bonjour à toi jeune dresseur.
    Ceci est ton espace.

    Tu peux y personnaliser tes albums, consulter les liens entre tes dex et tes données personnelles.
    Amuse toi bien.
```

In `translations/messages+intl-icu.en.yaml`, change the `trainer.welcome` block to:

```yaml
  welcome: |
    Hello, young trainer.
    This is your space.

    Here you can personalize your albums, and view the links between your dexes and your personal data.
    Have fun.
```

- [ ] **Step 6: Run the tests to verify they pass**

Run: `docker compose exec php php vendor/bin/phpunit tests/src/Integration/Controller/Trainer/TrainerPageTest.php`
Expected: PASS (all four tests).

Also run the filters test, unaffected by this task but exercising the same controller:

Run: `docker compose exec php php vendor/bin/phpunit tests/src/Integration/Controller/Trainer/TrainerPageFiltersTest.php`
Expected: PASS (unchanged).

- [ ] **Step 7: Commit**

```bash
git add src/Controller/TrainerIndexController.php templates/Trainer/index.html.twig \
  translations/messages+intl-icu.fr.yaml translations/messages+intl-icu.en.yaml \
  tests/src/Integration/Controller/Trainer/TrainerPageTest.php
git rm templates/Trainer/_section.html.twig public/js/trainer_tabs.js
git commit -m "refactor: slim the trainer customization page down to just customization"
```

---

### Task 5: Shared tab navigation across the three pages

**Files:**
- Create: `templates/Trainer/_tabs.html.twig`
- Modify: `templates/Trainer/index.html.twig`
- Modify: `templates/Trainer/links.html.twig`
- Modify: `templates/Trainer/personnal_data.html.twig`
- Modify: `tests/src/Integration/Controller/Trainer/TrainerPageTest.php`
- Modify: `tests/src/Integration/Controller/Trainer/TrainerLinksPageTest.php`
- Modify: `tests/src/Integration/Controller/Trainer/TrainerPersonnalDataPageTest.php`

**Interfaces:**
- Consumes: routes `app_trainerindex_index` (existing), `app_trainerlinks_index` (Task 3), `app_trainerpersonnaldata_index` (Task 2); translation keys `trainer.dex.title`, `trainer.links.title`, `trainer.personnal_data.title` (all existing, unchanged).
- Produces: `Trainer/_tabs.html.twig` expects one Twig variable, `active`, one of `'customization' | 'links' | 'personnal_data'`.

- [ ] **Step 1: Write the failing assertions**

In `tests/src/Integration/Controller/Trainer/TrainerPageTest.php`, add to `trainerPage()` right after the `assertLogoutNavBar($crawler);` line:

```php
        $this->assertCount(3, $crawler->filter('#trainer-section-tab .nav-link'));
        $this->assertSame(
            '/fr/trainer',
            $crawler->filter('#trainer-section-tab .nav-link.active')->attr('href')
        );
```

In `tests/src/Integration/Controller/Trainer/TrainerLinksPageTest.php`, add to `linksPage()` right after the `assertCount(0, ...'.dex-links-tree')` line:

```php
        $this->assertCount(3, $crawler->filter('#trainer-section-tab .nav-link'));
        $this->assertSame(
            '/fr/trainer/links',
            $crawler->filter('#trainer-section-tab .nav-link.active')->attr('href')
        );
```

In `tests/src/Integration/Controller/Trainer/TrainerPersonnalDataPageTest.php`, add to `personnalDataPage()` right after the "Service d'identification" assertion:

```php
        $this->assertCount(3, $crawler->filter('#trainer-section-tab .nav-link'));
        $this->assertSame(
            '/fr/trainer/personnal_data',
            $crawler->filter('#trainer-section-tab .nav-link.active')->attr('href')
        );
```

- [ ] **Step 2: Run the tests to verify they fail**

Run: `docker compose exec php php vendor/bin/phpunit tests/src/Integration/Controller/Trainer/`
Expected: FAIL on the three new assertions — `#trainer-section-tab` doesn't exist on any of the three pages yet.

- [ ] **Step 3: Create the shared tabs partial**

Create `templates/Trainer/_tabs.html.twig`:

```twig
{% set trainerPages = [
  {'key': 'customization', 'label': 'trainer.dex.title', 'route': 'app_trainerindex_index'},
  {'key': 'links', 'label': 'trainer.links.title', 'route': 'app_trainerlinks_index'},
  {'key': 'personnal_data', 'label': 'trainer.personnal_data.title', 'route': 'app_trainerpersonnaldata_index'},
] %}

<ul class="nav nav-tabs nav-fill mb-4" id="trainer-section-tab" role="tablist">
  {% for page in trainerPages %}
    <li class="nav-item" role="presentation">
      <a
        class="nav-link{{ page.key == active ? ' active' : '' }}"
        {{ page.key == active ? 'aria-current="page"' : '' }}
        href="{{ path(page.route) }}"
      >
        {{ page.label|trans }}
      </a>
    </li>
  {% endfor %}
</ul>
```

- [ ] **Step 4: Include it in the three pages**

In `templates/Trainer/index.html.twig`, insert a new row right before the row that holds the welcome paragraph and `_dex.html.twig`:

```twig
  <div class="row">
    <div class="col-md-12 mx-auto">
      {{ include('Trainer/_tabs.html.twig', {'active': 'customization'}) }}
    </div>
  </div>

  <div class="row">
    <div class="col-md-12 mx-auto row">

      <p class="text-secondary">{{ 'trainer.welcome'|trans|htmlNl2br }}</p>

      {{ include('Trainer/Section/_dex.html.twig') }}
    </div>
  </div>
```

In `templates/Trainer/links.html.twig`, apply the same pattern before the row that includes `_links.html.twig`, with `{'active': 'links'}`:

```twig
  <div class="row">
    <div class="col-md-12 mx-auto">
      {{ include('Trainer/_tabs.html.twig', {'active': 'links'}) }}
    </div>
  </div>

  <div class="row">
    <div class="col-md-12 mx-auto row">
      {{ include('Trainer/Section/_links.html.twig') }}
    </div>
  </div>
```

In `templates/Trainer/personnal_data.html.twig`, same pattern before the row that includes `_personnal_data.html.twig`, with `{'active': 'personnal_data'}`:

```twig
  <div class="row">
    <div class="col-md-12 mx-auto">
      {{ include('Trainer/_tabs.html.twig', {'active': 'personnal_data'}) }}
    </div>
  </div>

  <div class="row">
    <div class="col-md-12 mx-auto row">
      {{ include('Trainer/Section/_personnal_data.html.twig') }}
    </div>
  </div>
```

- [ ] **Step 5: Run the tests to verify they pass**

Run: `docker compose exec php php vendor/bin/phpunit tests/src/Integration/Controller/Trainer/`
Expected: PASS (all tests across all four files in the directory).

- [ ] **Step 6: Commit**

```bash
git add templates/Trainer/_tabs.html.twig templates/Trainer/index.html.twig \
  templates/Trainer/links.html.twig templates/Trainer/personnal_data.html.twig \
  tests/src/Integration/Controller/Trainer/TrainerPageTest.php \
  tests/src/Integration/Controller/Trainer/TrainerLinksPageTest.php \
  tests/src/Integration/Controller/Trainer/TrainerPersonnalDataPageTest.php
git commit -m "feat: add shared tab navigation across the three trainer pages"
```

---

### Task 6: Repoint the album offcanvas deep link

**Files:**
- Modify: `templates/Album/_offcanvas.html.twig`
- Modify: `tests/src/Integration/Controller/Album/Display/OffcanvasTest.php`

**Interfaces:**
- Consumes: route `app_trainerlinks_index` (Task 3).

- [ ] **Step 1: Write the failing assertion**

In `tests/src/Integration/Controller/Album/Display/OffcanvasTest.php`, add to `offcanvasHome()` right after the `assertResetLink($crawler, '/fr/album/home');` line:

```php
        $this->assertStringContainsString(
            '/fr/trainer/links',
            $crawler->filter('#album-links-section a')->attr('href') ?? ''
        );
```

- [ ] **Step 2: Run the test to verify it fails**

Run: `docker compose exec php php vendor/bin/phpunit --filter offcanvasHome tests/src/Integration/Controller/Album/Display/OffcanvasTest.php`
Expected: FAIL — the link still points at `app_trainerindex_index` with a `#section-links` fragment, not `/fr/trainer/links`.

- [ ] **Step 3: Repoint the link**

In `templates/Album/_offcanvas.html.twig`, change:

```twig
        <a href="{{ path('app_trainerindex_index', { '_fragment': 'section-links' }) }}">
```

to:

```twig
        <a href="{{ path('app_trainerlinks_index') }}">
```

- [ ] **Step 4: Run the test to verify it passes**

Run: `docker compose exec php php vendor/bin/phpunit --filter offcanvasHome tests/src/Integration/Controller/Album/Display/OffcanvasTest.php`
Expected: PASS.

Also run the full file to check for regressions in the other scenarios:

Run: `docker compose exec php php vendor/bin/phpunit tests/src/Integration/Controller/Album/Display/OffcanvasTest.php`
Expected: PASS (all tests).

- [ ] **Step 5: Commit**

```bash
git add templates/Album/_offcanvas.html.twig tests/src/Integration/Controller/Album/Display/OffcanvasTest.php
git commit -m "fix: point the album links offcanvas deep link at the new trainer links page"
```

---

### Task 7: Full-suite verification

**Files:** none (verification only).

- [ ] **Step 1: Run the full integration suite**

Run: `make tests-integration`
Expected: PASS, no regressions outside the files touched above.

- [ ] **Step 2: Run code quality gates**

Run: `make code-quality`
Expected: PASS. If PHPStan/Psalm/PHPMD baselines need regenerating because of the new controllers (unlikely — they mirror existing controllers' shape exactly), regenerate per `CLAUDE.md`'s baseline-update commands and commit the baseline diff separately.

- [ ] **Step 3: Run coverage and mutation measures**

Run: `make measures`
Expected: 100% coverage, 100% Mutation Score Index. New controllers are trivial (thin render calls, already covered by their integration tests); if MSI flags a surviving mutant, add the missing assertion to the relevant test file from Tasks 1–3 and re-run.

- [ ] **Step 4: Manual smoke check (optional, browser tests not run automatically)**

Run: `make start` if the stack isn't already up, then visit (as a fake trainer session):

```
http://localhost/fr/connect/f/c?t=trainer
http://localhost/fr/trainer
http://localhost/fr/trainer/links
http://localhost/fr/trainer/personnal_data
```

Confirm: three real page loads (URL changes each time), tab bar highlights the current page on each, logout is only in the bottom nav bar (not on any of the three pages), and at a narrow viewport (<576px) opening the hamburger menu shows all nav items including logout with none hidden behind another.

No commit for this task — it's verification of the work already committed in Tasks 1–6.
