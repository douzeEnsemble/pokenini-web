<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller\AlbumController;

use App\Security\User;
use App\Tests\Common\Traits\TestNavTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class AlbumControllerAccessTest extends WebTestCase
{
    use TestNavTrait;

    public function testAccessOwnPublicAlbum(): void
    {
        $client = static::createClient();

        $user = new User('789465465489');
        $user->addTrainerRole();
        $client->loginUser($user);

        $crawler = $client->request('GET', '/fr/album/r/home');

        $this->assertCount(1, $crawler->filter('.navbar-nav #share-link'));
        $this->assertCount(0, $crawler->filter('.navbar-nav #private-tag'));
    }

    public function testAccessOwnPrivateAlbum(): void
    {
        $client = static::createClient();

        $user = new User('789465465489');
        $user->addTrainerRole();
        $client->loginUser($user);

        $crawler = $client->request('GET', '/fr/album/r/demo');

        $this->assertCount(0, $crawler->filter('.navbar-nav #share-link'));
        $this->assertCount(1, $crawler->filter('.navbar-nav #private-tag'));
    }

    public function testAccessAnotherPublicAlbum(): void
    {
        $client = static::createClient();

        $user = new User('789465465489');
        $user->addTrainerRole();
        $client->loginUser($user);

        $crawler = $client->request('GET', '/fr/album/r/demo?t=7b52009b64fd0a2a49e6d8a939753077792b0554');

        $this->assertCount(1, $crawler->filter('.navbar-nav #share-link'));
        $this->assertCount(0, $crawler->filter('.navbar-nav #private-tag'));
    }

    public function testAccessAnotherPrivateAlbum(): void
    {
        $client = static::createClient();

        $user = new User('789465465489');
        $user->addTrainerRole();
        $client->loginUser($user);

        $client->request('GET', '/fr/album/r/home?t=7b52009b64fd0a2a49e6d8a939753077792b0554');

        $this->assertResponseStatusCodeSame(404);
    }
}
