<?php

declare(strict_types=1);

namespace App\Tests\Browser\Accessibility;

use App\Tests\Browser\AbstractBrowserTestCase;
use App\Tests\Utils\GetUserToken;
use Facebook\WebDriver\WebDriverDimension;
use Facebook\WebDriver\WebDriverKeys;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Panther\Client;

/**
 * A11Y-03: what a user does with the keyboard alone.
 *
 * @internal
 */
#[CoversNothing]
final class KeyboardTest extends AbstractBrowserTestCase
{
    #[Test]
    public function skipLinkMovesTheFocusToTheContent(): void
    {
        $client = $this->getNewClient();
        $this->loginTrainer($client);
        $client->request('GET', '/fr/album/demolite');

        $this->press($client, WebDriverKeys::TAB);
        $this->assertSame('#main-container', $this->activeElement($client, 'getAttribute("href")'), 'the skip link is the first stop');

        $this->press($client, WebDriverKeys::ENTER);
        $this->assertSame('main-container', $this->activeElement($client, 'id'), 'the skip link moves the focus to the content');

        $this->press($client, WebDriverKeys::TAB);
        $this->assertTrue(
            (bool) $client->executeScript('return document.getElementById("main-container").contains(document.activeElement);'),
            'the next stop is inside the content, not back in the navigation',
        );
    }

    #[Test]
    public function offcanvasKeepsTheFocusInsideAndGivesItBack(): void
    {
        $client = $this->getNewClient();
        $this->loginTrainer($client);
        $client->request('GET', '/fr/album/demolite');

        $client->executeScript('document.querySelector(".open-offcanvas").focus();');
        $this->press($client, WebDriverKeys::ENTER);
        $this->assertSelectorWillBeVisible('#offcanvas');
        $this->waitUntil($client, fn (): bool => $this->insideOffcanvas($client), 'the focus to enter the offcanvas');

        for ($i = 0; $i < 40; ++$i) {
            $this->press($client, WebDriverKeys::TAB);
            $this->assertTrue($this->insideOffcanvas($client), "Tab #{$i} left the offcanvas");
        }

        $this->press($client, WebDriverKeys::ESCAPE);
        $this->assertSelectorWillNotBeVisible('#offcanvas');
        $this->assertTrue(
            (bool) $client->executeScript('return document.activeElement.classList.contains("open-offcanvas");'),
            'Escape gives the focus back to the button that opened the offcanvas',
        );
    }

    #[Test]
    public function morePanelOpensAndClosesFromTheKeyboard(): void
    {
        $client = $this->getNewClient();
        $client->manage()->window()->setSize(new WebDriverDimension(390, 800));
        $this->loginTrainer($client);
        $client->request('GET', '/fr');

        $client->executeScript('document.querySelector(".nav-more button").focus();');
        $this->press($client, WebDriverKeys::ENTER);
        // "show" comes when the opening transition ends: Escape closes the panel only from then on
        $this->assertSelectorWillExist('#navbarNav.show');
        $this->assertSelectorAttributeContains('.nav-more button', 'aria-expanded', 'true');

        $this->press($client, WebDriverKeys::ESCAPE);
        $this->assertSelectorWillNotBeVisible('#navbarNav');
        $this->assertTrue(
            (bool) $client->executeScript('return document.activeElement === document.querySelector(".nav-more button");'),
            'Escape gives the focus back to the "more" button',
        );
    }

    #[Test]
    public function progressBarLegendOpensFromTheKeyboardAndClosesWithEscape(): void
    {
        $client = $this->getNewClient();
        $this->loginTrainer($client);
        $client->request('GET', '/fr/album/demolite');

        $client->executeScript('document.querySelector(".progress[data-bs-toggle=popover]").focus();');
        $this->press($client, WebDriverKeys::ENTER);
        $this->assertSelectorWillBeVisible('.popover');

        $this->press($client, WebDriverKeys::ESCAPE);
        // Bootstrap removes the popover from the DOM once it is hidden
        $this->waitUntil(
            $client,
            static fn (): bool => (bool) $client->executeScript('return 0 === document.querySelectorAll(".popover").length;'),
            'the legend to close',
        );
        $this->assertTrue(
            (bool) $client->executeScript('return document.activeElement.classList.contains("progress");'),
            'the focus stays on the bar once the legend is closed',
        );
    }

    private function loginTrainer(Client $client): void
    {
        $user = GetUserToken::getFakeUserToken();
        $user->addTrainerRole();
        $this->loginUser($client, $user);
    }

    private function press(Client $client, string $key): void
    {
        $client->getWebDriver()->switchTo()->activeElement()->sendKeys($key);
    }

    private function activeElement(Client $client, string $property): mixed
    {
        return $client->executeScript('return document.activeElement.'.$property.';');
    }

    private function insideOffcanvas(Client $client): bool
    {
        return (bool) $client->executeScript('return document.getElementById("offcanvas").contains(document.activeElement);');
    }
}
