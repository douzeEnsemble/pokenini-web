<?php

declare(strict_types=1);

namespace App\Tests\Browser\Admin;

use App\Tests\Browser\AbstractBrowserTestCase;
use App\Tests\Common\Traits\TestNavTrait;
use App\Tests\Utils\GetUserToken;
use Facebook\WebDriver\Exception\NoSuchElementException;
use Facebook\WebDriver\Exception\StaleElementReferenceException;
use Facebook\WebDriver\WebDriverBy;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\Test;

/**
 * @internal
 */
#[CoversNothing]
final class ForceActionTest extends AbstractBrowserTestCase
{
    use TestNavTrait;

    #[Test]
    public function dismissingConfirmationKeepsActionPending(): void
    {
        $client = $this->getNewClient();

        $user = GetUserToken::getFakeUserToken('109903422692691644111', 'TestProvider');
        $user->addAdminRole();
        $this->loginUser($client, $user);

        $client->request('GET', '/fr/istration/update_data');

        // Sentinel wiped out by any real page load (navigation destroys the JS context), unlike
        // the `data-updated-state` attribute which can't distinguish "no navigation happened" from
        // "navigation is still in flight when we happen to check" — this is what actually proves
        // event.preventDefault() ran and no form submission occurred.
        $client->executeScript('window.__noNav = true;');

        $button = $client->findElement(WebDriverBy::cssSelector('#update_games_collections_and_dex button.admin-item-cta'));
        $client->executeScript('arguments[0].scrollIntoView({block: "center", inline: "nearest"});', [$button]);
        $button->click();

        $message = $this->answerConfirmModal($client, '.confirm-modal-cancel');
        $this->assertStringContainsString('Une exécution est en cours depuis', $message);
        // The modal removes itself from the DOM once hidden (Panther's waitForInvisibility() times out on a missing element).
        $this->waitUntil(
            $client,
            static fn () => (bool) $client->executeScript('return null === document.querySelector(".confirm-modal-ok");'),
            'the confirmation modal to disappear',
        );

        $this->assertTrue((bool) $client->executeScript('return window.__noNav === true;'));
    }

    #[Test]
    public function acceptingConfirmationSubmitsTheForm(): void
    {
        $client = $this->getNewClient();

        $user = GetUserToken::getFakeUserToken('109903422692691644222', 'TestProvider');
        $user->addAdminRole();
        $this->loginUser($client, $user);

        $client->request('GET', '/fr/istration/update_data');

        $button = $client->findElement(WebDriverBy::cssSelector('#update_games_collections_and_dex button.admin-item-cta'));
        $client->executeScript('arguments[0].scrollIntoView({block: "center", inline: "nearest"});', [$button]);
        $button->click();

        $this->answerConfirmModal($client, '.confirm-modal-ok');

        // The click triggers a real page navigation (POST then 302 redirect back to the GET page).
        // Panther's own submit() helper has Firefox-specific waiting logic for exactly this reason
        // (vendor/symfony/panther/src/Client.php:227-246); since we bypass that helper (see note
        // below), wait explicitly for the post-redirect DOM state instead of assuming the click()
        // call already blocked until navigation finished.
        $this->waitUntil($client, static function () use ($client) {
            try {
                return '' !== $client->findElement(WebDriverBy::cssSelector('#update_games_collections_and_dex'))->getAttribute('data-updated-state');
            } catch (NoSuchElementException|StaleElementReferenceException) {
                // The page is being replaced by the navigation: keep waiting.
                return false;
            }
        }, 'the page to show the updated state of the action');

        $crawler = $client->refreshCrawler();
        $this->assertNotSame('', $crawler->filter('#update_games_collections_and_dex')->attr('data-updated-state'));
    }
}
