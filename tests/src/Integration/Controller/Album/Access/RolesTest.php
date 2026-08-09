<?php

declare(strict_types=1);

namespace App\Tests\Integration\Controller\Album\Access;

use App\Controller\AlbumIndexController;
use App\Tests\Common\Traits\TestNavTrait;
use App\Tests\Utils\GetUserToken;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @internal
 */
#[CoversClass(AlbumIndexController::class)]
#[Group('api-mocked-testing')]
final class RolesTest extends WebTestCase
{
    use TestNavTrait;

    #[Test]
    public function readNonConnectedNoToken(): void
    {
        $client = self::createClient();

        $client->request('GET', '/fr/album/home');

        $this->assertResponseStatusCodeSame(404);
    }

    #[Test]
    public function readNonConnected(): void
    {
        $client = self::createClient();

        $crawler = $client->request('GET', '/fr/album/demo?t=7b52009b64fd0a2a49e6d8a939753077792b0554');

        $this->assertNoConnectedNavBar($crawler);
    }

    #[Test]
    public function readTrainer(): void
    {
        $client = self::createClient();

        $user = GetUserToken::getFakeUserToken();
        $user->addTrainerRole();
        $client->loginUser($user, 'web');

        $crawler = $client->request('GET', '/fr/album/home');

        $this->assertTrainerAlbumNavBar($crawler);

        $this->assertCountFilter($crawler, 1, 'script[src="/js/album-edit.js"]');
        $this->assertCountFilter($crawler, 1, '.album-all-catch-state-read-action');
        $this->assertCountFilter($crawler, 1, '.album-all-catch-state-edit-action');
    }

    #[Test]
    public function readCollector(): void
    {
        $client = self::createClient();

        $user = GetUserToken::getFakeUserToken();
        $user->addCollectorRole();
        $client->loginUser($user, 'web');

        $crawler = $client->request('GET', '/fr/album/home');

        $this->assertTrainerAlbumNavBar($crawler);

        $this->assertCountFilter($crawler, 1, 'script[src="/js/album-edit.js"]');
        $this->assertCountFilter($crawler, 1, '.album-all-catch-state-read-action');
        $this->assertCountFilter($crawler, 1, '.album-all-catch-state-edit-action');
    }

    #[Test]
    public function readAdmin(): void
    {
        $client = self::createClient();

        $user = GetUserToken::getFakeUserToken('8764532', 'TestProvider');
        $user->addTrainerRole();
        $user->addAdminRole();
        $client->loginUser($user, 'web');

        $crawler = $client->request('GET', '/fr/album/demolite');

        $this->assertAdminAlbumNavBar($crawler);

        $this->assertCountFilter($crawler, 1, 'script[src="/js/album-edit.js"]');
        $this->assertCountFilter($crawler, 1, '.album-all-catch-state-read-action');
        $this->assertCountFilter($crawler, 1, '.album-all-catch-state-edit-action');
    }

    #[Test]
    public function writeNonConnected(): void
    {
        $client = self::createClient();

        $client->request('GET', '/fr/album/home?t=7b52009b64fd0a2a49e6d8a939753077792b0554');

        $this->assertResponseStatusCodeSame(404);
    }

    #[Test]
    public function writeTrainer(): void
    {
        $client = self::createClient();

        $user = GetUserToken::getFakeUserToken();
        $user->addTrainerRole();
        $client->loginUser($user, 'web');

        $crawler = $client->request('GET', '/fr/album/home');

        $this->assertTrainerAlbumNavBar($crawler);

        $this->assertCountFilter($crawler, 1, 'script[src="/js/album-edit.js"]');
        $this->assertCountFilter($crawler, 1, '.album-all-catch-state-read-action');
        $this->assertCountFilter($crawler, 1, '.album-all-catch-state-edit-action');
    }

    #[Test]
    public function writeCollector(): void
    {
        $client = self::createClient();

        $user = GetUserToken::getFakeUserToken();
        $user->addTrainerRole();
        $client->loginUser($user, 'web');

        $crawler = $client->request('GET', '/fr/album/home');

        $this->assertTrainerAlbumNavBar($crawler);

        $this->assertCountFilter($crawler, 1, 'script[src="/js/album-edit.js"]');
        $this->assertCountFilter($crawler, 1, '.album-all-catch-state-read-action');
        $this->assertCountFilter($crawler, 1, '.album-all-catch-state-edit-action');
    }

    #[Test]
    public function writeAdmin(): void
    {
        $client = self::createClient();

        $user = GetUserToken::getFakeUserToken('8764532', 'TestProvider');
        $user->addTrainerRole();
        $user->addAdminRole();
        $client->loginUser($user, 'web');

        $crawler = $client->request('GET', '/fr/album/demolite');

        $this->assertAdminAlbumNavBar($crawler);

        $this->assertCountFilter($crawler, 1, 'script[src="/js/album-edit.js"]');
        $this->assertCountFilter($crawler, 1, '.album-all-catch-state-read-action');
        $this->assertCountFilter($crawler, 1, '.album-all-catch-state-edit-action');
    }

    #[Test]
    public function writeTrainerOnPremiumDex(): void
    {
        $client = self::createClient();

        $user = GetUserToken::getFakeUserToken();
        $user->addTrainerRole();
        $client->loginUser($user, 'web');

        $crawler = $client->request('GET', '/fr/album/homepokemongo');

        $this->assertTrainerAlbumNavBar($crawler);

        $this->assertCountFilter($crawler, 0, 'script[src="/js/album-edit.js"]');
        $this->assertCountFilter($crawler, 0, '.album-all-catch-state-read-action');
        $this->assertCountFilter($crawler, 0, '.album-all-catch-state-edit-action');
    }

    #[Test]
    public function writeCollectorOnPremiumDex(): void
    {
        $client = self::createClient();

        $user = GetUserToken::getFakeUserToken();
        $user->addTrainerRole();
        $user->addCollectorRole();
        $client->loginUser($user, 'web');

        $crawler = $client->request('GET', '/fr/album/homepokemongo');

        $this->assertTrainerAlbumNavBar($crawler);

        $this->assertCountFilter($crawler, 1, 'script[src="/js/album-edit.js"]');
        $this->assertCountFilter($crawler, 1, '.album-all-catch-state-read-action');
        $this->assertCountFilter($crawler, 1, '.album-all-catch-state-edit-action');
    }

    #[Test]
    public function writeAdminOnPremiumDex(): void
    {
        $client = self::createClient();

        $user = GetUserToken::getFakeUserToken();
        $user->addTrainerRole();
        $user->addCollectorRole();
        $user->addAdminRole();
        $client->loginUser($user, 'web');

        $crawler = $client->request('GET', '/fr/album/homepokemongo');

        $this->assertAdminAlbumNavBar($crawler);

        $this->assertCountFilter($crawler, 1, 'script[src="/js/album-edit.js"]');
        $this->assertCountFilter($crawler, 1, '.album-all-catch-state-read-action');
        $this->assertCountFilter($crawler, 1, '.album-all-catch-state-edit-action');
    }
}
