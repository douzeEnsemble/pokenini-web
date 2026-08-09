<?php

declare(strict_types=1);

namespace App\Tests\Browser\Admin;

use App\Tests\Browser\AbstractBrowserTestCase;
use App\Tests\Common\Traits\TestNavTrait;
use App\Tests\Utils\GetUserToken;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\Test;

/**
 * @internal
 */
#[CoversNothing]
final class ToggleActionsTest extends AbstractBrowserTestCase
{
    use TestNavTrait;

    #[Test]
    public function toggleActions(): void
    {
        $client = $this->getNewClient();

        $user = GetUserToken::getFakeUserToken('109903422692691643666', 'TestProvider');
        $user->addAdminRole();
        $this->loginUser($client, $user);

        $client->request('GET', '/fr/istration/actions');

        $this->assertSelectorIsVisible('.admin-item-update_labels .admin-item-current');
        $this->assertSelectorIsNotVisible('.admin-item-update_labels .admin-item-last');

        $client->click(
            $client
                ->getCrawler()
                ->filter('.admin-item-update_labels .admin-item-current .admin-item-toggle')
                ->link()
        );

        $this->assertSelectorWillNotBeVisible('.admin-item-update_labels .admin-item-current');
        $this->assertSelectorWillBeVisible('.admin-item-update_labels .admin-item-last');

        $client->click(
            $client
                ->getCrawler()
                ->filter('.admin-item-update_labels .admin-item-last .admin-item-toggle')
                ->link()
        );

        $this->assertSelectorWillBeVisible('.admin-item-update_labels .admin-item-current');
        $this->assertSelectorWillNotBeVisible('.admin-item-update_labels .admin-item-last');
    }
}
