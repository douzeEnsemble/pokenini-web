<?php

declare(strict_types=1);

namespace App\Tests\Functional\Album\Access;

use App\Security\User;
use App\Tests\Common\Traits\TestNavTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class AccessReleasedTest extends WebTestCase
{
    use TestNavTrait;

    public function testAccessOwnReleasedAlbum(): void
    {
        $client = static::createClient();

        $user = new User('8764532');
        $user->addTrainerRole();
        $client->loginUser($user);

        $crawler = $client->request('GET', '/fr/album/home');

        $this->assertCountFilter($crawler, 0, '.navbar-nav #share-link');
        $this->assertCountFilter($crawler, 0, '.navbar-nav #private-tag');
    }

    public function testTrainerAccessUnreleasedAlbum(): void
    {
        $client = static::createClient();

        $user = new User('8764532');
        $user->addTrainerRole();
        $client->loginUser($user);

        $client->request('GET', '/fr/album/goldsilvercrystal');

        $this->assertResponseStatusCodeSame(404);
    }

    public function testAdminAccessUnreleasedAlbum(): void
    {
        $client = static::createClient();

        $user = new User('8764532');
        $user->addAdminRole();
        $client->loginUser($user);

        $crawler = $client->request('GET', '/fr/album/goldsilvercrystal');

        $this->assertCountFilter($crawler, 0, '.navbar-nav #share-link');
        $this->assertCountFilter($crawler, 0, '.navbar-nav #private-tag');
    }

    public function testAccessAnotherUnreleasedAlbum(): void
    {
        $client = static::createClient();

        $user = new User('8764532');
        $user->addAdminRole();
        $client->loginUser($user);

        $crawler = $client->request('GET', '/fr/album/goldsilvercrystal?t=159bb9b6d090a313087d2f26135970c2db49ee72');

        $this->assertCountFilter($crawler, 0, '.navbar-nav #share-link');
        $this->assertCountFilter($crawler, 0, '.navbar-nav #private-tag');
    }
}
