<?php

declare(strict_types=1);

namespace App\Tests\Functional\Album\Common;

use App\Security\User;
use App\Tests\Common\Traits\TestNavTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

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

        $user = new User('789465465489');
        $user->addTrainerRole();
        $client->loginUser($user);

        $crawler = $client->request('GET', '/fr/album/home');

        $this->assertTrainerAlbumNavBar($crawler);
    }

    public function testReadAdmin(): void
    {
        $client = static::createClient();

        $user = new User('8764532');
        $user->addTrainerRole();
        $user->addAdminRole();
        $client->loginUser($user);

        $crawler = $client->request('GET', '/fr/album/home');

        $this->assertAdminAlbumNavBar($crawler);
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

        $user = new User('789465465489');
        $user->addTrainerRole();
        $client->loginUser($user);

        $crawler = $client->request('GET', '/fr/album/home');

        $this->assertTrainerAlbumNavBar($crawler);
    }

    public function testWriteAdmin(): void
    {
        $client = static::createClient();

        $user = new User('8764532');
        $user->addTrainerRole();
        $user->addAdminRole();
        $client->loginUser($user);

        $crawler = $client->request('GET', '/fr/album/home');

        $this->assertAdminAlbumNavBar($crawler);
    }
}
