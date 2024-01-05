<?php

declare(strict_types=1);

namespace App\Tests\Browser\Admin;

use App\Security\User;
use App\Tests\Common\Traits\TestNavTrait;
use App\Tests\Browser\AbstractBrowserTestCase;

/**
 * @group browser-testing
 */
class ToggleActionsTest extends AbstractBrowserTestCase
{
    use TestNavTrait;

    public function testToggleActions(): void
    {
        $client = $this->getNewClient();

        $user = new User('109903422692691643666');
        $user->addAdminRole();
        $this->loginUser($client, $user);

        $client->request('GET', '/fr/istration');

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
