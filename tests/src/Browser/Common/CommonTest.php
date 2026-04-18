<?php

declare(strict_types=1);

namespace App\Tests\Browser\Common;

use App\Security\User;
use App\Tests\Browser\AbstractBrowserTestCase;
use App\Tests\Common\Traits\TestNavTrait;
use League\OAuth2\Client\Token\AccessToken;
use PHPUnit\Framework\Attributes\CoversNothing;

/**
 * @internal
 */
#[CoversNothing]
final class CommonTest extends AbstractBrowserTestCase
{
    use TestNavTrait;

    public function testOpenCookieManager(): void
    {
        $client = $this->getNewClient();

        $user = new User('789465465489', 'TestProvider', new AccessToken(['access_token' => sha1('789465465489')]));
        $user->addTrainerRole();
        $this->loginUser($client, $user);

        $client->request('GET', '/fr');

        $this->assertSelectorIsNotVisible('#tarteaucitron');

        $client->executeScript("document.querySelector('#navbarNav .cookie-manager button').click();");

        $this->assertSelectorWillBeVisible('#tarteaucitron');
    }
}
