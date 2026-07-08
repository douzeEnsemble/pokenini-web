<?php

declare(strict_types=1);

namespace App\Tests\Integration\Controller\Common;

use App\Controller\ConnectController;
use App\Controller\HomeController;
use App\Tests\Common\Traits\TestNavTrait;
use App\Tests\Utils\GetUserToken;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @internal
 */
#[CoversClass(HomeController::class)]
#[CoversClass(ConnectController::class)]
final class CommonItemsTest extends WebTestCase
{
    use TestNavTrait;

    public function testHome(): void
    {
        $client = self::createClient();

        $user = GetUserToken::getFakeUserToken('8764532', 'TestProvider');
        $user->addTrainerRole();
        $user->addAdminRole();
        $client->loginUser($user, 'web');

        $this->assertCommonItems($client, '/fr/album/dex');
    }

    public function testConnect(): void
    {
        $client = self::createClient();

        $this->assertCommonItems($client, '/fr/connect');
    }

    public function testAdministration(): void
    {
        $client = self::createClient();

        $user = GetUserToken::getFakeUserToken('8764532', 'TestProvider');
        $user->addAdminRole();
        $client->loginUser($user, 'web');

        $this->assertCommonItems($client, '/fr/istration/actions');
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
