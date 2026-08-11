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

        $crawler = $client->request('GET', '/fr/album/demolite');

        $client->executeScript("document.querySelector('.open-offcanvas').click()");
        $this->assertSelectorWillBeVisible('#offcanvas');
        $client->waitFor('#offcanvas.show:not(.showing)');
        $client->waitFor('.dex-pick-card.linked');

        $this->assertSelectorWillBeVisible('.dex-pick-card[data-dex-slug="redgreenblueyellow"] a.btn.btn-light.btn-sm');
        $this->assertSelectorWillBeVisible('.dex-pick-card[data-dex-slug="redgreenblueyellow"] a.btn.btn-light.btn-sm i.bi-eye-fill');

        $link = $crawler->filter('.dex-pick-card[data-dex-slug="redgreenblueyellow"] a.btn.btn-light.btn-sm');
        $this->assertStringContainsString('Voir', trim($link->text()));
        $this->assertStringContainsString('/fr/album/redgreenblueyellow', $link->attr('href') ?? '');
    }
}
