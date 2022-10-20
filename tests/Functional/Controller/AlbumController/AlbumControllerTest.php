<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller\AlbumController;

use App\Tests\Common\Traits\TestNavTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class AlbumControllerTest extends WebTestCase
{
    use TestNavTrait;

    public function testDisplayForm(): void
    {
        $client = static::createClient();

        $client->request('GET', '/fr/album/r/home');

        $mainCrawler = $client->getCrawler();

        $this->assertEquals('Printemps', $mainCrawler->filter('#deerling-spring .album-case-forms')->text());
    }

    public function testNonDisplayForm(): void
    {
        $client = static::createClient();

        $client->request('GET', '/fr/album/r/homepokemongo');

        $mainCrawler = $client->getCrawler();

        $this->assertEquals(' ', $mainCrawler->filter('#deerling-spring .album-case-forms')->text());
    }

    public function testReadNonConnected(): void
    {
        $client = static::createClient();

        $client->request('GET', '/fr/album/r/home');

        $this->assertNoConnectedNavBar($client->getCrawler());
    }

    public function testReadConnected(): void
    {
        $client = static::createClient();

        $client->request('GET', '/fr/album/r/home', [], [], [
            'PHP_AUTH_USER' => 'renaud',
            'PHP_AUTH_PW'   => 'douze',
        ]);

        $this->assertConnectedAlbumNavBar($client->getCrawler());
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

        $client->request('GET', '/fr/album/w/home', [], [], [
            'PHP_AUTH_USER' => 'renaud',
            'PHP_AUTH_PW'   => 'douze',
        ]);

        $this->assertConnectedAlbumNavBar($client->getCrawler());
    }

    public function testFilterCatchStateNo(): void
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/fr/album/r/demo/no');

        $this->assertCount(
            1732,
            $crawler->filter('.album-case')
        );

        $this->assertCount(0, $crawler->filter('h2.box'));
        $this->assertCount(1, $crawler->filter('#bulbasaur'));
        $this->assertCount(0, $crawler->filter('#venusaur-f'));
        $this->assertCount(0, $crawler->filter('#charmander'));
    }

    public function testFilterCatchStateYes(): void
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/fr/album/r/demo/yes');

        $this->assertCount(
            2,
            $crawler->filter('.album-case')
        );

        $this->assertCount(0, $crawler->filter('h2.box'));
        $this->assertCount(0, $crawler->filter('#bulbasaur'));
        $this->assertCount(0, $crawler->filter('#venusaur-f'));
        $this->assertCount(1, $crawler->filter('#charmander'));
    }

    public function testFilterCatchStateUnknown(): void
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/fr/album/r/demo/unknown');

        $this->assertCount(
            0,
            $crawler->filter('.album-case')
        );

        $this->assertCount(0, $crawler->filter('h2.box'));
    }
}
