<?php

declare(strict_types=1);

namespace App\Tests\Functional\Album\Access;

use App\Controller\AlbumIndexController;
use App\Security\User;
use App\Tests\Common\Traits\TestNavTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @internal
 */
#[CoversClass(AlbumIndexController::class)]
class RolesTest extends WebTestCase
{
    use TestNavTrait;

    public function testReadNonConnectedNoToken(): void
    {
        $client = static::createClient();

        $client->request('GET', '/fr/album/home');

        $this->assertResponseStatusCodeSame(404);
    }

    public function testReadNonConnected(): void
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/fr/album/demo?t=7b52009b64fd0a2a49e6d8a939753077792b0554');

        $this->assertNoConnectedNavBar($crawler);
    }

    public function testReadTrainer(): void
    {
        $client = static::createClient();

        $user = new User('789465465489', 'TestProvider');
        $user->addTrainerRole();
        $client->loginUser($user, 'web');

        $crawler = $client->request('GET', '/fr/album/home');

        $this->assertTrainerAlbumNavBar($crawler);

        $this->assertCountFilter($crawler, 1, 'script[src="/js/album-edit.js"]');
        $this->assertCountFilter($crawler, 1, '.album-all-catch-state-read-action');
        $this->assertCountFilter($crawler, 1, '.album-all-catch-state-edit-action');
    }

    public function testReadCollector(): void
    {
        $client = static::createClient();

        $user = new User('789465465489', 'TestProvider');
        $user->addCollectorRole();
        $client->loginUser($user, 'web');

        $crawler = $client->request('GET', '/fr/album/home');

        $this->assertTrainerAlbumNavBar($crawler);

        $this->assertCountFilter($crawler, 1, 'script[src="/js/album-edit.js"]');
        $this->assertCountFilter($crawler, 1, '.album-all-catch-state-read-action');
        $this->assertCountFilter($crawler, 1, '.album-all-catch-state-edit-action');
    }

    public function testReadAdmin(): void
    {
        $client = static::createClient();

        $user = new User('8764532', 'TestProvider');
        $user->addTrainerRole();
        $user->addAdminRole();
        $client->loginUser($user, 'web');

        $crawler = $client->request('GET', '/fr/album/demolite');

        $this->assertAdminAlbumNavBar($crawler);

        $this->assertCountFilter($crawler, 1, 'script[src="/js/album-edit.js"]');
        $this->assertCountFilter($crawler, 1, '.album-all-catch-state-read-action');
        $this->assertCountFilter($crawler, 1, '.album-all-catch-state-edit-action');
    }

    public function testWriteNonConnected(): void
    {
        $client = static::createClient();

        $client->request('GET', '/fr/album/home?t=7b52009b64fd0a2a49e6d8a939753077792b0554');

        $this->assertResponseStatusCodeSame(404);
    }

    public function testWriteTrainer(): void
    {
        $client = static::createClient();

        $user = new User('789465465489', 'TestProvider');
        $user->addTrainerRole();
        $client->loginUser($user, 'web');

        $crawler = $client->request('GET', '/fr/album/home');

        $this->assertTrainerAlbumNavBar($crawler);

        $this->assertCountFilter($crawler, 1, 'script[src="/js/album-edit.js"]');
        $this->assertCountFilter($crawler, 1, '.album-all-catch-state-read-action');
        $this->assertCountFilter($crawler, 1, '.album-all-catch-state-edit-action');
    }

    public function testWriteCollector(): void
    {
        $client = static::createClient();

        $user = new User('789465465489', 'TestProvider');
        $user->addTrainerRole();
        $client->loginUser($user, 'web');

        $crawler = $client->request('GET', '/fr/album/home');

        $this->assertTrainerAlbumNavBar($crawler);

        $this->assertCountFilter($crawler, 1, 'script[src="/js/album-edit.js"]');
        $this->assertCountFilter($crawler, 1, '.album-all-catch-state-read-action');
        $this->assertCountFilter($crawler, 1, '.album-all-catch-state-edit-action');
    }

    public function testWriteAdmin(): void
    {
        $client = static::createClient();

        $user = new User('8764532', 'TestProvider');
        $user->addTrainerRole();
        $user->addAdminRole();
        $client->loginUser($user, 'web');

        $crawler = $client->request('GET', '/fr/album/demolite');

        $this->assertAdminAlbumNavBar($crawler);

        $this->assertCountFilter($crawler, 1, 'script[src="/js/album-edit.js"]');
        $this->assertCountFilter($crawler, 1, '.album-all-catch-state-read-action');
        $this->assertCountFilter($crawler, 1, '.album-all-catch-state-edit-action');
    }

    public function testWriteTrainerOnPremiumDex(): void
    {
        $client = static::createClient();

        $user = new User('789465465489', 'TestProvider');
        $user->addTrainerRole();
        $client->loginUser($user, 'web');

        $crawler = $client->request('GET', '/fr/album/homepokemongo');

        $this->assertTrainerAlbumNavBar($crawler);

        $this->assertCountFilter($crawler, 0, 'script[src="/js/album-edit.js"]');
        $this->assertCountFilter($crawler, 0, '.album-all-catch-state-read-action');
        $this->assertCountFilter($crawler, 0, '.album-all-catch-state-edit-action');
    }

    public function testWriteCollectorOnPremiumDex(): void
    {
        $client = static::createClient();

        $user = new User('789465465489', 'TestProvider');
        $user->addTrainerRole();
        $user->addCollectorRole();
        $client->loginUser($user, 'web');

        $crawler = $client->request('GET', '/fr/album/homepokemongo');

        $this->assertTrainerAlbumNavBar($crawler);

        $this->assertCountFilter($crawler, 1, 'script[src="/js/album-edit.js"]');
        $this->assertCountFilter($crawler, 1, '.album-all-catch-state-read-action');
        $this->assertCountFilter($crawler, 1, '.album-all-catch-state-edit-action');
    }

    public function testWriteAdminOnPremiumDex(): void
    {
        $client = static::createClient();

        $user = new User('789465465489', 'TestProvider');
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
