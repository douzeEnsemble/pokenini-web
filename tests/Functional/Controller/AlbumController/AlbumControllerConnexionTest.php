<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller\AlbumController;

use App\Security\User;
use App\Tests\Common\Traits\TestNavTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class AlbumControllerConnexionTest extends WebTestCase
{
    use TestNavTrait;

    public function testReadNonConnected(): void
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/fr/album/r/home');

        $this->assertNoConnectedNavBar($crawler);
    }

    public function testReadTrainer(): void
    {
        $client = static::createClient();

        $user = new User('789465465489');
        $user->addTrainerRole();
        $client->loginUser($user);

        $crawler = $client->request('GET', '/fr/album/r/home');

        $this->assertTrainerAlbumNavBar($crawler);
    }

    public function testReadAdmin(): void
    {
        $client = static::createClient();

        $user = new User('8764532');
        $user->addTrainerRole();
        $user->addAdminRole();
        $client->loginUser($user);

        $crawler = $client->request('GET', '/fr/album/r/home');

        $this->assertAdminAlbumNavBar($crawler);
    }

    public function testWriteNonConnected(): void
    {
        $client = static::createClient();

        $client->request('GET', '/fr/album/w/home');

        $this->assertResponseStatusCodeSame(307);
    }

    public function testWriteTrainer(): void
    {
        $client = static::createClient();

        $user = new User('789465465489');
        $user->addTrainerRole();
        $client->loginUser($user);

        $crawler = $client->request('GET', '/fr/album/w/home');

        $this->assertTrainerAlbumNavBar($crawler);
    }

    public function testWriteAdmin(): void
    {
        $client = static::createClient();

        $user = new User('8764532');
        $user->addTrainerRole();
        $user->addAdminRole();
        $client->loginUser($user);

        $crawler = $client->request('GET', '/fr/album/w/home');

        $this->assertAdminAlbumNavBar($crawler);
    }
}
