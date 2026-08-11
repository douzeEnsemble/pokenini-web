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
final class OffcanvasLinksSelfLinkGuardTest extends AbstractBrowserTestCase
{
    use TestNavTrait;

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

        $client->executeScript("createLink('goldsilvercrystal', 'goldsilvercrystal');");

        // Without the guard, createLink would make a fetch POST that would succeed (201)
        // and show the success toast. With the guard, no fetch is made and no toast appears.
        // Use a small sleep to allow the toast to appear if the request was made
        sleep(1);
        $this->assertSelectorIsNotVisible('#linksToastSuccess');
    }
}
