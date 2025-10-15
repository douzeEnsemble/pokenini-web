<?php

declare(strict_types=1);

namespace App\Tests\Integration\Controller\Common;

use App\Controller\ConnectController;
use App\Controller\HomeController;
use App\Security\User;
use App\Tests\Common\Traits\TestNavTrait;
use League\OAuth2\Client\Token\AccessToken;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @internal
 */
#[CoversClass(HomeController::class)]
#[CoversClass(ConnectController::class)]
class CommonItemsTest extends WebTestCase
{
    use TestNavTrait;

    public function testHome(): void
    {
        $client = static::createClient();

        $user = new User('8764532', 'TestProvider', new AccessToken(['access_token' => sha1('8764532')]));
        $user->addAdminRole();
        $client->loginUser($user, 'web');

        $this->assertCommonItems($client, '/fr');
    }

    public function testConnect(): void
    {
        $client = static::createClient();

        $this->assertCommonItems($client, '/fr/connect');
    }

    public function testAdministration(): void
    {
        $client = static::createClient();

        $user = new User('8764532', 'TestProvider', new AccessToken(['access_token' => sha1('8764532')]));
        $user->addAdminRole();
        $client->loginUser($user, 'web');

        $this->assertCommonItems($client, '/fr/istration');
    }

    private function assertCommonItems(KernelBrowser $client, string $url): void
    {
        $crawler = $client->request('GET', $url);

        $this->assertCountFilter($crawler, 1, 'link[sizes="180x180"]');
        $this->assertCountFilter($crawler, 1, 'link[sizes="32x32"]');
        $this->assertCountFilter($crawler, 1, 'link[sizes="16x16"]');
        $this->assertCountFilter($crawler, 1, 'link[rel="manifest"]');

        $this->assertCountFilter($crawler, 1, '.navbar-brand img');
    }
}
