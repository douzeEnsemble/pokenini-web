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
final class OffcanvasLinksViewButtonTest extends AbstractBrowserTestCase
{
    use TestNavTrait;

    #[Test]
    public function viewButtonMatchesTrainerPageStyleAndIsAlwaysVisible(): void
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

        $this->assertSelectorWillBeVisible('.dex-pick-card[data-dex-slug="redgreenblueyellow"] a.btn.btn-light.btn-sm');
        $this->assertSelectorWillBeVisible('.dex-pick-card[data-dex-slug="redgreenblueyellow"] a.btn.btn-light.btn-sm i.bi-eye-fill');

        $link = $client->getCrawler()->filter('.dex-pick-card[data-dex-slug="redgreenblueyellow"] a.btn.btn-light.btn-sm');
        $this->assertStringContainsString('Voir', trim($link->text()));
        $this->assertStringContainsString('/fr/album/redgreenblueyellow', $link->attr('href') ?? '');
    }

    #[Test]
    public function viewButtonDoesNotOverlapTheUnlinkControlOnLinkedCards(): void
    {
        $client = $this->getNewClient();

        $user = GetUserToken::getFakeUserToken('109903422692691643666', 'TestProvider');
        $user->addTrainerRole();
        $this->loginUser($client, $user);

        $client->request('GET', '/fr/album/demolite');

        $client->executeScript("document.querySelector('.open-offcanvas').click()");
        $this->assertSelectorWillBeVisible('#offcanvas');
        $client->waitFor('#offcanvas.show:not(.showing)');
        // goldsilvercrystal is already linked in the Moco fixture, so its card
        // is the one carrying the absolutely positioned unlink control.
        $client->waitFor('.dex-pick-card[data-dex-slug="goldsilvercrystal"].linked .unlink-btn');

        // The "Voir" button is a full-width in-flow element at the top of the
        // card; the unlink control is absolutely positioned. Their boxes must
        // not intersect, otherwise the X paints over the button's left edge.
        $this->assertSame('no-overlap', $client->executeScript("
            var card = document.querySelector('.dex-pick-card[data-dex-slug=\"goldsilvercrystal\"]');
            var view = card.querySelector('.dex-pick-view').getBoundingClientRect();
            var unlink = card.querySelector('.unlink-btn').getBoundingClientRect();
            var gap = Math.round(unlink.top - view.bottom);

            return gap >= 0 ? 'no-overlap' : 'overlap:' + gap;
        "));
    }
}
