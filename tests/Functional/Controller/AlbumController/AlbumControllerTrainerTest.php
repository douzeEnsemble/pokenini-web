<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller\AlbumController;

use App\Security\User;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class AlbumControllerTrainerTest extends WebTestCase
{
    public function testAlbumTrainerLogged(): void
    {
        $client = static::createClient();

        $user = new User('789465465489');
        $user->addTrainerRole();
        $client->loginUser($user);

        $crawler = $client->request('GET', '/fr/album/r/demo');

        $this->assertResponseIsSuccessful();

        $this->assertCount(1738, $crawler->filter('.album-case'));

        $this->assertCount(1732, $crawler->filter('.album-case.catch-state-no'));
        $this->assertCount(1, $crawler->filter('.album-case.catch-state-toevolve'));
        $this->assertCount(1, $crawler->filter('.album-case.catch-state-tobreed'));
        $this->assertCount(1, $crawler->filter('.album-case.catch-state-totransfer'));
        $this->assertCount(1, $crawler->filter('.album-case.catch-state-totrade'));
        $this->assertCount(2, $crawler->filter('.album-case.catch-state-yes'));

        $this->assertCount(0, $crawler->filter('.another-trainer-album'));
    }

    public function testAlbumTrainerGiven(): void
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/fr/album/r/demo?t=7b52009b64fd0a2a49e6d8a939753077792b0554');

        $this->assertResponseIsSuccessful();

        $this->assertCount(1738, $crawler->filter('.album-case'));

        $this->assertCount(1718, $crawler->filter('.album-case.catch-state-no'));
        $this->assertCount(3, $crawler->filter('.album-case.catch-state-toevolve'));
        $this->assertCount(3, $crawler->filter('.album-case.catch-state-tobreed'));
        $this->assertCount(3, $crawler->filter('.album-case.catch-state-totransfer'));
        $this->assertCount(2, $crawler->filter('.album-case.catch-state-totrade'));
        $this->assertCount(9, $crawler->filter('.album-case.catch-state-yes'));

        $this->assertCount(1, $crawler->filter('.another-trainer-album'));
    }

    public function testAlbumTrainerLoggedAndGiven(): void
    {
        $client = static::createClient();

        $user = new User('789465465489');
        $user->addTrainerRole();
        $client->loginUser($user);

        $crawler = $client->request('GET', '/fr/album/r/demo?t=7b52009b64fd0a2a49e6d8a939753077792b0554');

        $this->assertResponseIsSuccessful();

        $this->assertCount(1738, $crawler->filter('.album-case'));

        $this->assertCount(1718, $crawler->filter('.album-case.catch-state-no'));
        $this->assertCount(3, $crawler->filter('.album-case.catch-state-toevolve'));
        $this->assertCount(3, $crawler->filter('.album-case.catch-state-tobreed'));
        $this->assertCount(3, $crawler->filter('.album-case.catch-state-totransfer'));
        $this->assertCount(2, $crawler->filter('.album-case.catch-state-totrade'));
        $this->assertCount(9, $crawler->filter('.album-case.catch-state-yes'));

        $this->assertCount(1, $crawler->filter('.another-trainer-album'));
    }
}
