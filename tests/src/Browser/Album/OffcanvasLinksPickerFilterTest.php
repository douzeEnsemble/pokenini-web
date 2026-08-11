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
final class OffcanvasLinksPickerFilterTest extends AbstractBrowserTestCase
{
    use TestNavTrait;

    #[Test]
    public function searchBoxIsRemoved(): void
    {
        $client = $this->getNewClient();

        $user = GetUserToken::getFakeUserToken('109903422692691643666', 'TestProvider');
        $user->addTrainerRole();
        $this->loginUser($client, $user);

        $crawler = $client->request('GET', '/fr/album/demolite');

        $this->assertCount(0, $crawler->filter('#dex-picker-search'));
    }

    #[Test]
    public function privacyFilterHidesNonMatchingCards(): void
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

        $this->assertSelectorWillBeVisible('.dex-pick-card[data-dex-slug="redgreenblueyellow"]');
        $this->assertSelectorWillBeVisible('.dex-pick-card[data-dex-slug="swordshield"]');

        $client->executeScript("
            var el = document.getElementById('dex-picker-filter-privacy');
            el.value = '1';
            el.dispatchEvent(new Event('change'));
        ");

        $this->assertSelectorWillBeVisible('.dex-pick-card[data-dex-slug="redgreenblueyellow"]');
        $this->assertSelectorWillNotBeVisible('.dex-pick-card[data-dex-slug="swordshield"]');
    }

    #[Test]
    public function releasedFilterIsHiddenForNonAdmin(): void
    {
        $client = $this->getNewClient();

        $user = GetUserToken::getFakeUserToken('109903422692691643666', 'TestProvider');
        $user->addTrainerRole();
        $this->loginUser($client, $user);

        $client->request('GET', '/fr/album/demolite');

        $client->executeScript("document.querySelector('.open-offcanvas').click()");
        $this->assertSelectorWillBeVisible('#offcanvas');
        $client->waitFor('#offcanvas.show:not(.showing)');

        $this->assertCount(0, $client->getCrawler()->filter('#dex-picker-filter-released'));
    }

    #[Test]
    public function linkedFilterShowsOnlyAlreadyLinkedDexes(): void
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

        $client->executeScript("
            var el = document.getElementById('dex-picker-filter-linked');
            el.value = '1';
            el.dispatchEvent(new Event('change'));
        ");

        $this->assertSelectorWillBeVisible('.dex-pick-card[data-dex-slug="goldsilvercrystal"]');
        $this->assertSelectorWillBeVisible('.dex-pick-card[data-dex-slug="rubysapphireemerald"]');
        $this->assertSelectorWillNotBeVisible('.dex-pick-card[data-dex-slug="redgreenblueyellow"]');
    }

    #[Test]
    public function hidingTheSelectedCardWithAFilterClearsTheSelection(): void
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

        $client->executeScript("document.querySelector('.dex-pick-card[data-dex-slug=\"swordshield\"]').click()");

        $client->waitFor('.dex-pick-card[data-dex-slug="swordshield"].selected');
        $this->assertNull($client->getCrawler()->filter('#create-link')->attr('disabled'));

        // The privacy filter hides swordshield (see privacyFilterHidesNonMatchingCards):
        // "Créer le lien" must not stay armed on a card the user can no longer see.
        $client->executeScript("
            var el = document.getElementById('dex-picker-filter-privacy');
            el.value = '1';
            el.dispatchEvent(new Event('change'));
        ");

        $this->assertSelectorWillNotBeVisible('.dex-pick-card[data-dex-slug="swordshield"]');
        $this->assertNotNull($client->getCrawler()->filter('#create-link')->attr('disabled'));
        $this->assertCount(0, $client->getCrawler()->filter('.dex-pick-card.selected'));
    }
}
