<?php

declare(strict_types=1);

namespace App\Tests\Functional\Album\Access;

use App\Security\User;
use App\Tests\Common\Traits\TestNavTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class TrainerTest extends WebTestCase
{
    use TestNavTrait;

    public function testAlbumTrainerLogged(): void
    {
        $client = static::createClient();

        $user = new User('789465465489');
        $user->addTrainerRole();
        $client->loginUser($user);

        $crawler = $client->request('GET', '/fr/album/demo');

        $this->assertResponseIsSuccessful();

        $this->assertCountFilter($crawler, 1738, '.album-case');

        $this->assertCountFilter($crawler, 1732, '.album-case.catch-state-no');
        $this->assertCountFilter($crawler, 1, '.album-case.catch-state-toevolve');
        $this->assertCountFilter($crawler, 1, '.album-case.catch-state-tobreed');
        $this->assertCountFilter($crawler, 1, '.album-case.catch-state-totransfer');
        $this->assertCountFilter($crawler, 1, '.album-case.catch-state-totrade');
        $this->assertCountFilter($crawler, 2, '.album-case.catch-state-yes');

        $this->assertCountFilter($crawler, 0, '.another-trainer-album');
    }

    public function testAlbumTrainerGiven(): void
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/fr/album/demo?t=7b52009b64fd0a2a49e6d8a939753077792b0554');

        $this->assertResponseIsSuccessful();

        $this->assertCountFilter($crawler, 1738, '.album-case');

        $this->assertCountFilter($crawler, 1718, '.album-case.catch-state-no');
        $this->assertCountFilter($crawler, 3, '.album-case.catch-state-toevolve');
        $this->assertCountFilter($crawler, 3, '.album-case.catch-state-tobreed');
        $this->assertCountFilter($crawler, 3, '.album-case.catch-state-totransfer');
        $this->assertCountFilter($crawler, 2, '.album-case.catch-state-totrade');
        $this->assertCountFilter($crawler, 9, '.album-case.catch-state-yes');

        $this->assertCountFilter($crawler, 1, '.another-trainer-album');
    }

    public function testAlbumTrainerLoggedAndGiven(): void
    {
        $client = static::createClient();

        $user = new User('789465465489');
        $user->addTrainerRole();
        $client->loginUser($user);

        $crawler = $client->request('GET', '/fr/album/demo?t=7b52009b64fd0a2a49e6d8a939753077792b0554');

        $this->assertResponseIsSuccessful();

        $this->assertCountFilter($crawler, 1738, '.album-case');

        $this->assertCountFilter($crawler, 1718, '.album-case.catch-state-no');
        $this->assertCountFilter($crawler, 3, '.album-case.catch-state-toevolve');
        $this->assertCountFilter($crawler, 3, '.album-case.catch-state-tobreed');
        $this->assertCountFilter($crawler, 3, '.album-case.catch-state-totransfer');
        $this->assertCountFilter($crawler, 2, '.album-case.catch-state-totrade');
        $this->assertCountFilter($crawler, 9, '.album-case.catch-state-yes');

        $this->assertCountFilter($crawler, 1, '.another-trainer-album');
    }
}
