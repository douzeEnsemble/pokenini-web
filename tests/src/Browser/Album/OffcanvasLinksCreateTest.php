<?php

declare(strict_types=1);

namespace App\Tests\Browser\Album;

use App\Tests\Browser\AbstractBrowserTestCase;
use App\Tests\Common\Traits\TestNavTrait;
use App\Tests\Utils\GetUserToken;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;

/**
 * @internal
 */
#[CoversNothing]
#[Group('api-mocked-testing')]
final class OffcanvasLinksCreateTest extends AbstractBrowserTestCase
{
    use TestNavTrait;

    #[Test]
    public function selectingACardAndCreatingTheLinkShowsTheSuccessToastAndResetsTheSelection(): void
    {
        $client = $this->getNewClient();

        $user = GetUserToken::getFakeUserToken('109903422692691643666', 'TestProvider');
        $user->addTrainerRole();
        $this->loginUser($client, $user);

        $client->request('GET', '/fr/album/demolite');

        $client->executeScript("document.querySelector('.open-offcanvas').click()");
        $this->assertSelectorWillBeVisible('#offcanvas');
        $client->waitFor('#offcanvas.show:not(.showing)');
        $client->waitFor('.dex-pick-card.linked');

        // swordshield is neither the current dex nor one of the two dexes the
        // Moco fixture already reports as linked (goldsilvercrystal,
        // rubysapphireemerald), so it is selectable.
        $client->executeScript("document.querySelector('.dex-pick-card[data-dex-slug=\"swordshield\"]').click()");

        $client->waitFor('.dex-pick-card[data-dex-slug="swordshield"].selected');
        $this->assertNull($client->getCrawler()->filter('#create-link')->attr('disabled'));

        $client->executeScript("document.getElementById('create-link').click()");

        $this->assertSelectorWillBeVisible('#linksToastSuccess');

        // The grid is re-rendered after the POST: the pick must not stay armed,
        // otherwise a second click would silently re-POST the same link.
        $client->waitFor('#create-link:disabled');
        $this->assertNotNull($client->getCrawler()->filter('#create-link')->attr('disabled'));
        $this->assertCount(0, $client->getCrawler()->filter('.dex-pick-card.selected'));
    }

    #[Test]
    public function currentDexCardIsNeverSelectable(): void
    {
        $client = $this->getNewClient();

        $user = GetUserToken::getFakeUserToken('109903422692691643666', 'TestProvider');
        $user->addTrainerRole();
        $this->loginUser($client, $user);

        $client->request('GET', '/fr/album/goldsilvercrystal');

        $client->executeScript("document.querySelector('.open-offcanvas').click()");
        $this->assertSelectorWillBeVisible('#offcanvas');
        $client->waitFor('#offcanvas.show:not(.showing)');
        $client->waitFor('.dex-pick-card.linked');

        $client->executeScript("document.querySelector('.dex-pick-card-current').click()");

        $disabled = $client->getCrawler()->filter('#create-link')->attr('disabled');
        $this->assertNotNull($disabled);
        $this->assertCount(0, $client->getCrawler()->filter('.dex-pick-card.selected'));
    }

    #[Test]
    public function createLinkGuardsAgainstSelfLinkEvenIfCalledDirectly(): void
    {
        $client = $this->getNewClient();

        $user = GetUserToken::getFakeUserToken('109903422692691643666', 'TestProvider');
        $user->addTrainerRole();
        $this->loginUser($client, $user);

        $client->request('GET', '/fr/album/goldsilvercrystal');

        $client->executeScript("document.querySelector('.open-offcanvas').click()");
        $this->assertSelectorWillBeVisible('#offcanvas');
        $client->waitFor('#offcanvas.show:not(.showing)');
        // Waiting for the rendered grid guarantees the initial loadLinks() fetch
        // already resolved, so the counter below only sees createLink's own calls.
        $client->waitFor('.dex-pick-card.linked');

        $client->executeScript('window.__fetchCalls = 0; const originalFetch = window.fetch; window.fetch = function () { window.__fetchCalls++; return originalFetch.apply(this, arguments); };');

        $client->executeScript("createLink('goldsilvercrystal', 'goldsilvercrystal');");

        // Without the guard, createLink would POST to /album_link/goldsilvercrystal.
        // Counting fetch calls is deterministic: asserting on the (already hidden)
        // success toast would pass on the first poll even without the guard.
        $this->assertSame(0, $client->executeScript('return window.__fetchCalls;'));
        $this->assertSelectorWillNotBeVisible('#linksToastSuccess');
    }
}
