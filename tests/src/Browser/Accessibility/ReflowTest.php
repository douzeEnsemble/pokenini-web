<?php

declare(strict_types=1);

namespace App\Tests\Browser\Accessibility;

use App\Tests\Browser\AbstractBrowserTestCase;
use App\Tests\Utils\GetUserToken;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

/**
 * A11Y-06: at 320 px wide, with the text enlarged to 150 %, no page scrolls horizontally (WCAG 1.4.10).
 *
 * The text is enlarged because a wider system font on a runner is enough to overflow a bar
 * that cannot wrap.
 *
 * @internal
 */
#[CoversNothing]
final class ReflowTest extends AbstractBrowserTestCase
{
    #[Test]
    #[DataProvider('pageProvider')]
    public function pageDoesNotScrollHorizontally(string $role, string $path): void
    {
        $client = $this->getNewClient();

        if ('anon' !== $role) {
            $user = GetUserToken::getFakeUserToken();
            'admin' === $role ? $user->addAdminRole() : $user->addTrainerRole();
            $this->loginUser($client, $user);
        }

        // The page is shown in a 320 px wide frame: a browser window cannot be narrower than about
        // 500 px, while the media queries of a frame follow its own width.
        $client->request('GET', '/fr/policy');
        $client->executeScript(
            <<<'JS'
                document.body.innerHTML = '';
                const frame = document.createElement('iframe');
                frame.id = 'reflow';
                frame.style.cssText = 'width:320px;height:800px;border:0';
                frame.src = arguments[0];
                document.body.appendChild(frame);
                JS,
            [$path],
        );
        $this->waitUntil($client, static fn (): bool => (bool) $client->executeScript(
            'const d = document.getElementById("reflow").contentDocument; return null !== d && "complete" === d.readyState && d.location.pathname !== "about:blank";',
        ), 'the frame to load');

        /** @var array{scrollWidth: int, clientWidth: int} $widths */
        $widths = $client->executeScript(
            <<<'JS'
                const root = document.getElementById('reflow').contentDocument.documentElement;
                root.style.fontSize = '150%';
                return {scrollWidth: root.scrollWidth, clientWidth: root.clientWidth};
                JS,
        );

        $this->assertSame(320, $widths['clientWidth'], 'the frame is 320 px wide');
        $this->assertLessThanOrEqual($widths['clientWidth'], $widths['scrollWidth'], "{$path} scrolls horizontally at 320 px");
    }

    /**
     * @return iterable<string, array{string, string}>
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
            yield "{$role} {$path}" => [$role, $path];
        }
    }
}
