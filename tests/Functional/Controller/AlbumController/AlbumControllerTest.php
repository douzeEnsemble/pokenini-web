<?php

namespace App\Tests\Functional\Controller\AlbumController;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class AlbumControllerTest extends WebTestCase
{
    public function testDisplayForm(): void
    {
        $client = static::createClient();

        $client->request('GET', '/album/home');

        $mainCrawler = $client->getCrawler();

        $this->assertEquals('Printemps', $mainCrawler->filter('#deerling-spring .album-case-forms')->text());
    }
    public function testNonDisplayForm(): void
    {
        $client = static::createClient();

        $client->request('GET', '/album/homepokemongo');

        $mainCrawler = $client->getCrawler();

        $this->assertEquals(' ', $mainCrawler->filter('#deerling-spring .album-case-forms')->text());
    }
}
