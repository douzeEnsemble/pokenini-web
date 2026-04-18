<?php

declare(strict_types=1);

namespace App\Tests\Browser\Admin;

use App\Security\User;
use App\Tests\Browser\AbstractBrowserTestCase;
use App\Tests\Common\Traits\TestNavTrait;
use League\OAuth2\Client\Token\AccessToken;
use PHPUnit\Framework\Attributes\CoversNothing;

/**
 * @internal
 */
#[CoversNothing]
final class ToggleActionsTest extends AbstractBrowserTestCase
{
    use TestNavTrait;

    public function testToggleActions(): void
    {
        $client = $this->getNewClient();

        $user = new User('109903422692691643666', 'TestProvider', new AccessToken(['access_token' => sha1('109903422692691643666')]));
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
