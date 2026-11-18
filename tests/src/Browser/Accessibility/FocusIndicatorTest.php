<?php

declare(strict_types=1);

namespace App\Tests\Browser\Accessibility;

use App\Tests\Browser\AbstractBrowserTestCase;
use App\Tests\Utils\GetUserToken;
use Facebook\WebDriver\WebDriverKeys;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Panther\Client;

/**
 * A11Y-03: the focus is always visible. Each stop of the Tab key shows a solid outline of 2px
 * at least, which holds 3:1 against the colour around it (WCAG 2.4.7 and 1.4.11), in both themes.
 *
 * @internal
 */
#[CoversNothing]
final class FocusIndicatorTest extends AbstractBrowserTestCase
{
    private const MAX_STOPS = 40;

    /**
     * Returns null when the focused element shows a good outline, otherwise what is wrong with it.
     */
    private const CHECK_FOCUSED_ELEMENT = <<<'JS'
        const parse = (value) => {
            const match = value.match(/rgba?\(([^)]+)\)/);
            if (!match) { return null; }
            const parts = match[1].split(/[ ,\/]+/).filter(Boolean).map(Number);
            return {r: parts[0], g: parts[1], b: parts[2], a: parts.length > 3 ? parts[3] : 1};
        };
        const luminance = (c) => {
            const channel = (v) => { v /= 255; return v <= 0.03928 ? v / 12.92 : Math.pow((v + 0.055) / 1.055, 2.4); };
            return 0.2126 * channel(c.r) + 0.7152 * channel(c.g) + 0.0722 * channel(c.b);
        };
        const ratio = (a, b) => {
            const l1 = luminance(a), l2 = luminance(b);
            return (Math.max(l1, l2) + 0.05) / (Math.min(l1, l2) + 0.05);
        };
        const blend = (f, b) => ({r: f.r * f.a + b.r * (1 - f.a), g: f.g * f.a + b.g * (1 - f.a), b: f.b * f.a + b.b * (1 - f.a), a: 1});
        const dark = document.documentElement.getAttribute('data-bs-theme') === 'dark';
        const surface = (element) => {
            for (let node = element; node; node = node.parentElement) {
                const style = getComputedStyle(node);
                const color = parse(style.backgroundColor);
                if (color && color.a > 0.99) { return color; }
            }
            return dark ? {r: 33, g: 37, b: 41, a: 1} : {r: 255, g: 255, b: 255, a: 1};
        };
        // Some cards animate their shadow: measure the outline once the transitions are over
        document.getAnimations().forEach((animation) => { try { animation.finish(); } catch (error) { /* an endless animation (striped progress bar) has no end */ } });
        const element = document.activeElement;
        if (!element || element === document.body) { return null; }
        // Reached with the Tab key, the element matches :focus-visible, unless the browser window has no system focus
        if (!element.matches(':focus-visible')) { return 'no focus'; }
        const box = element.getBoundingClientRect();
        if (0 === box.width || 0 === box.height) { return null; }
        const label = element.tagName.toLowerCase() + '[' + (element.getAttribute('aria-label') || element.innerText || element.title || element.getAttribute('href') || '').trim().replace(/\s+/g, ' ').slice(0, 30) + ']';
        const style = getComputedStyle(element);
        if ('none' === style.outlineStyle || parseFloat(style.outlineWidth) < 2) {
            return label + ' has no outline of 2px';
        }
        const color = parse(style.outlineColor);
        if (!color) { return label + ' has an outline colour that is not read: ' + style.outlineColor; }
        // The outline sits around the element (or inside it, at a negative offset): the colour next to it is the surface of its parent
        const around = surface(element.parentElement || element);
        let result = ratio(blend(color, around), around);
        // Two tones (WCAG technique C40): the second one is a box-shadow, one of the two has to stand out
        const shadow = (style.boxShadow.match(/rgba?\([^)]+\)/) || [])[0];
        const second = shadow ? parse(shadow) : null;
        if (second) { result = Math.max(result, ratio(blend(second, around), around)); }
        return result < 3 ? label + ' outline contrast ' + result.toFixed(1) + ':1' : null;
        JS;

    #[Test]
    #[DataProvider('pageProvider')]
    public function everyStopOfTheTabKeyShowsAnOutline(string $role, string $path, string $theme): void
    {
        $client = $this->getNewClient();

        $this->signIn($client, $role, $path);
        $client->request('GET', $path);
        $client->executeScript(sprintf("localStorage.setItem('theme', %s);", json_encode($theme, \JSON_THROW_ON_ERROR)));
        $client->request('GET', $path);

        // Without the system focus on the window, no element matches :focus
        $client->getWebDriver()->switchTo()->window($client->getWindowHandle());
        $client->executeScript('window.focus();');
        $this->waitUntil($client, static fn (): bool => (bool) $client->executeScript('return document.hasFocus();'), 'the page to have the focus');

        $problems = [];
        $stops = 0;
        for ($i = 0; $i < self::MAX_STOPS; ++$i) {
            $client->getWebDriver()->switchTo()->activeElement()->sendKeys(WebDriverKeys::TAB);
            $problem = $client->executeScript(self::CHECK_FOCUSED_ELEMENT);
            self::assertTrue(null === $problem || is_string($problem));
            if ('no focus' === $problem) {
                self::markTestSkipped('The browser window has no system focus (Firefox without a window manager): the focused element does not match :focus-visible, so there is no outline to measure.');
            }
            if (null !== $problem) {
                $problems[$problem] = $problem;
            }
            ++$stops;
        }

        $this->assertSame([], array_values($problems), "focus indicator problems on {$path} ({$theme} theme), {$stops} stops");
    }

    /**
     * @return iterable<string, array{string, string, string}>
     */
    public static function pageProvider(): iterable
    {
        $pages = [
            ['anon', '/fr/connect'],
            ['anon', '/fr/hub'],
            ['trainer', '/fr/album/demolite'],
            ['trainer', '/fr/election/dex'],
            ['trainer', '/fr/game/color'],
            ['trainer', '/fr/trainer/links'],
            ['admin', '/fr/istration/update_data'],
        ];

        foreach ($pages as [$role, $path]) {
            foreach (['light', 'dark'] as $theme) {
                yield "{$role} {$path} {$theme}" => [$role, $path, $theme];
            }
        }
    }

    private function signIn(Client $client, string $role, string $path): void
    {
        if ('anon' === $role) {
            // Refusing the cookies closes the consent dialog, which holds the focus
            $client->request('GET', $path);
            $client->executeScript("document.cookie = 'tarteaucitron=!matomocloud=false; path=/';");

            return;
        }

        $user = GetUserToken::getFakeUserToken();
        'admin' === $role ? $user->addAdminRole() : $user->addTrainerRole();
        $this->loginUser($client, $user);
    }
}
