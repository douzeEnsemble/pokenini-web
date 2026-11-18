<?php

declare(strict_types=1);

namespace App\Tests\Browser\Accessibility;

use App\Security\User;
use App\Tests\Browser\AbstractBrowserTestCase;
use App\Tests\Utils\GetUserToken;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Panther\Client;

/**
 * A11Y-02: every page is checked by axe-core against WCAG 2.2 A and AA, in the light and the dark theme.
 *
 * An exception is a rule disabled for the page it concerns, with its reason, never a whole page.
 *
 * @internal
 */
#[CoversNothing]
final class AccessibilityTest extends AbstractBrowserTestCase
{
    private const AXE_TAGS = ['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa', 'wcag22aa'];

    /**
     * Rules disabled for one page, with the reason.
     *
     * The edit pencils of the album are 16px icons whose hit area is widened to 24x24 CSS px by an
     * ::after pseudo-element (public/css/base.css, WCAG 2.5.8). axe measures the box of the button,
     * not that pseudo-element, and reports them as too small.
     */
    private const DISABLED_RULES = [
        '/fr/album/demolite' => ['target-size'],
    ];

    #[Test]
    #[DataProvider('pageProvider')]
    public function pageHasNoViolation(string $role, string $path, string $theme): void
    {
        $client = $this->getNewClient();

        $this->login($client, $role);
        $client->request('GET', $path);
        $client->executeScript(sprintf("localStorage.setItem('theme', %s);", json_encode($theme, \JSON_THROW_ON_ERROR)));
        $client->request('GET', $path);

        $this->assertSame([], $this->runAxe($client, self::DISABLED_RULES[$path] ?? []), "axe violations on {$path} ({$theme} theme)");
    }

    /**
     * @return iterable<string, array{string, string, string}>
     */
    public static function pageProvider(): iterable
    {
        $pages = [
            ['anon', '/fr'],
            ['anon', '/fr/hub'],
            ['anon', '/fr/connect'],
            ['anon', '/fr/policy'],
            ['trainer', '/fr/album/dex'],
            ['trainer', '/fr/album/demolite'],
            ['trainer', '/fr/election/dex'],
            ['trainer', '/fr/game'],
            ['trainer', '/fr/game/color'],
            ['trainer', '/fr/trainer'],
            ['trainer', '/fr/trainer/links'],
            ['admin', '/fr/istration/update_data'],
        ];

        foreach ($pages as [$role, $path]) {
            foreach (['light', 'dark'] as $theme) {
                yield "{$role} {$path} {$theme}" => [$role, $path, $theme];
            }
        }
    }

    private function login(Client $client, string $role): void
    {
        if ('anon' === $role) {
            return;
        }

        $user = GetUserToken::getFakeUserToken();
        $this->addRole($user, $role);
        $this->loginUser($client, $user);
    }

    private function addRole(User $user, string $role): void
    {
        match ($role) {
            'admin' => $user->addAdminRole(),
            default => $user->addTrainerRole(),
        };
    }

    /**
     * Runs axe in the page and returns its violations, one line per rule and node.
     *
     * axe is injected as a page script and its result is kept in the DOM: a WebDriver script may
     * run apart from the page (Firefox) and could not read the `axe` global.
     *
     * @param list<string> $disabledRules
     *
     * @return list<string>
     */
    private function runAxe(Client $client, array $disabledRules): array
    {
        $source = file_get_contents(__DIR__.'/../../../resources/axe/axe.min.js');
        self::assertIsString($source);

        $client->executeScript(
            <<<'JS'
                const script = document.createElement('script');
                script.textContent = arguments[0];
                document.head.appendChild(script);
                const run = document.createElement('script');
                run.textContent = `
                    axe.run(document, {runOnly: {type: 'tag', values: ${arguments[1]}}, rules: ${arguments[2]}}).then(function (results) {
                        const lines = [];
                        results.violations.forEach(function (violation) {
                            violation.nodes.forEach(function (node) {
                                lines.push(violation.id + ' (' + violation.impact + '): ' + node.target.join(' ') + ' | ' + violation.help);
                            });
                        });
                        document.body.dataset.axeResult = JSON.stringify(lines);
                    });
                `;
                document.head.appendChild(run);
                JS,
            [
                $source,
                json_encode(self::AXE_TAGS, \JSON_THROW_ON_ERROR),
                json_encode((object) array_fill_keys($disabledRules, ['enabled' => false]), \JSON_THROW_ON_ERROR),
            ],
        );

        $this->waitUntil(
            $client,
            static fn (): bool => (bool) $client->executeScript('return undefined !== document.body.dataset.axeResult;'),
            'the axe result',
        );

        $json = $client->executeScript('return document.body.dataset.axeResult;');
        self::assertIsString($json);

        /** @var list<string> $lines */
        $lines = json_decode($json, true, 512, \JSON_THROW_ON_ERROR);

        return $lines;
    }
}
