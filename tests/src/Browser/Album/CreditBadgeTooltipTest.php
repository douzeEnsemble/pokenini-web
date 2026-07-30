<?php

declare(strict_types=1);

namespace App\Tests\Browser\Album;

use App\Tests\Browser\AbstractBrowserTestCase;
use App\Tests\Common\Traits\TestNavTrait;
use App\Tests\Utils\GetUserToken;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\Group;

/**
 * Regression guard: on touch devices there is no hover, so a tap on the
 * credit badge is the only signal it ever gets. It used to fire a direct
 * window.open() on tap, sending mobile users to the credit link without
 * ever showing them the tooltip attribution. The badge tap must always
 * reveal the tooltip first; the credit link only lives inside its content.
 *
 * @internal
 */
#[CoversNothing]
#[Group('api-mocked-testing')]
final class CreditBadgeTooltipTest extends AbstractBrowserTestCase
{
    use TestNavTrait;

    public function testTappingCreditBadgeShowsTooltipInsteadOfNavigating(): void
    {
        $client = $this->getNewClient();

        $user = GetUserToken::getFakeUserToken('109903422692691643666', 'TestProvider');
        $user->addTrainerRole();
        $this->loginUser($client, $user);

        $client->request('GET', '/fr/album/demolite');

        $client->executeScript(<<<'JS'
                const modal = new bootstrap.Modal(document.getElementById('modal-bulbasaur'));
                modal.show();
            JS);
        $this->assertSelectorWillBeVisible('#modal-bulbasaur');

        $this->assertSelectorNotExists('.tooltip');

        $urlBeforeClick = $client->getCurrentURL();

        $client->executeScript('document.querySelector(\'#modal-bulbasaur .pokemon-image-credit\').click()');

        $this->assertSame($urlBeforeClick, $client->getCurrentURL());

        $this->assertSelectorWillBeVisible('.tooltip');
        $this->assertSelectorExists('.tooltip .tooltip-inner a[href*="pokemondb.net"]');
    }
}
