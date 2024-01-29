<?php

declare(strict_types=1);

namespace App\Tests\Functional\Album\Access;

use App\Security\User;
use App\Tests\Common\Traits\TestNavTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class AccessPrivateTest extends WebTestCase
{
    use TestNavTrait;

    public function testAccessOwnPublicAlbum(): void
    {
        $client = static::createClient();

        $user = new User('789465465489');
        $user->addTrainerRole();
        $client->loginUser($user);

        $crawler = $client->request('GET', '/fr/album/home');

        $this->assertCountFilter($crawler, 0, '.navbar-nav #share-link');
        $this->assertCountFilter($crawler, 0, '.navbar-nav #private-tag');
    }

    public function testAccessOwnPrivateAlbum(): void
    {
        $client = static::createClient();

        $user = new User('789465465489');
        $user->addTrainerRole();
        $client->loginUser($user);

        $crawler = $client->request('GET', '/fr/album/demo');

        $this->assertCountFilter($crawler, 0, '.navbar-nav #share-link');
        $this->assertCountFilter($crawler, 0, '.navbar-nav #private-tag');
    }

    public function testAccessAnotherPublicAlbum(): void
    {
        $client = static::createClient();

        $user = new User('789465465489');
        $user->addTrainerRole();
        $client->loginUser($user);

        $crawler = $client->request('GET', '/fr/album/demo?t=7b52009b64fd0a2a49e6d8a939753077792b0554');

        $this->assertCountFilter($crawler, 0, '.navbar-nav #share-link');
        $this->assertCountFilter($crawler, 0, '.navbar-nav #private-tag');
    }

    public function testAccessAnotherPrivateAlbum(): void
    {
        $client = static::createClient();

        $user = new User('789465465489');
        $user->addTrainerRole();
        $client->loginUser($user);

        $client->request('GET', '/fr/album/home?t=7b52009b64fd0a2a49e6d8a939753077792b0554');

        $this->assertResponseStatusCodeSame(404);
    }

    public function testAccessNonExistingAlbum(): void
    {
        $client = static::createClient();

        $user = new User('789465465489');
        $user->addTrainerRole();
        $client->loginUser($user);

        $client->request('GET', '/fr/album/douze');

        $this->assertResponseStatusCodeSame(404);
    }
}
