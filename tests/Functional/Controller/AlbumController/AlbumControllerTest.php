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

        $client->request('GET', '/fr/album/home');

        $mainCrawler = $client->getCrawler();

        $this->assertEquals('Printemps', $mainCrawler->filter('#deerling-spring .album-case-forms')->text());
    }

    public function testNonDisplayForm(): void
    {
        $client = static::createClient();

        $client->request('GET', '/fr/album/homepokemongo');

        $mainCrawler = $client->getCrawler();

        $this->assertEquals(' ', $mainCrawler->filter('#deerling-spring .album-case-forms')->text());
    }

    public function testNonConnected(): void
    {
        $client = static::createClient();

        $client->request('GET', '/fr/album/home');

        $this->assertNoConnectedNavBar($client->getCrawler());
    }

    public function testConnected(): void
    {
        $client = static::createClient();

        $client->request('GET', '/fr/album/home', [], [], [
            'PHP_AUTH_USER' => 'renaud',
            'PHP_AUTH_PW'   => 'douze',
        ]);

        $this->assertConnectedAlbumNavBar($client->getCrawler());
    }
}
