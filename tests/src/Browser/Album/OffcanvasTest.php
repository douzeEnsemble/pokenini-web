<?php

declare(strict_types=1);

namespace App\Tests\Browser\Album;

use App\Security\User;
use App\Tests\Browser\AbstractBrowserTestCase;
use App\Tests\Common\Traits\TestNavTrait;
use League\OAuth2\Client\Token\AccessToken;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\Group;

/**
 * @internal
 */
#[CoversNothing]
#[Group('api-mocked-testing')]
final class OffcanvasTest extends AbstractBrowserTestCase
{
    use TestNavTrait;

    public function testOffcanvas(): void
    {
        $client = $this->getNewClient();

        $user = new User('109903422692691643666', 'TestProvider', new AccessToken(['access_token' => sha1('109903422692691643666')]));
        $user->addTrainerRole();
        $this->loginUser($client, $user);

        $client->request('GET', '/fr/album/demolite');

        $this->assertSelectorIsNotVisible('#offcanvas');

        $client->executeScript("document.querySelector('.open-offcanvas').click()");

        $this->assertSelectorWillBeVisible('#offcanvas');

        $client->executeScript("document.querySelector('#offcanvas .btn-close').click()");

        $this->assertSelectorWillNotBeVisible('#offcanvas');
    }
}
