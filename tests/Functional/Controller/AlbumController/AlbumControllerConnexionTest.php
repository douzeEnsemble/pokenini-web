<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller\AlbumController;

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

    public function testReadConnected(): void
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/fr/album/r/home', [], [], [
            'PHP_AUTH_USER' => 'renaud',
            'PHP_AUTH_PW'   => 'douze',
        ]);

        $this->assertConnectedAlbumNavBar($crawler);
    }

    public function testWriteNonConnected(): void
    {
        $client = static::createClient();

        $client->request('GET', '/fr/album/w/home');

        $this->assertResponseStatusCodeSame(401);
    }

    public function testWriteConnected(): void
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/fr/album/w/home', [], [], [
            'PHP_AUTH_USER' => 'renaud',
            'PHP_AUTH_PW'   => 'douze',
        ]);

        $this->assertConnectedAlbumNavBar($crawler);
    }
}
