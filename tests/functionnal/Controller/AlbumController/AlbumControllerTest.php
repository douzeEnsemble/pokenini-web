<?php

namespace App\Tests\Functionnal\Controller\AlbumController;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class AlbumControllerTest extends WebTestCase
{
    public function testDisplayForm(): void
    {
        $client = static::createClient();

        $client->request('GET', '/album/home');

        $mainCrawler = $client->getCrawler();

        $this->assertCount(1738, $mainCrawler->filter('.album-case-forms'));
    }
    public function testNonDisplayForm(): void
    {
        $client = static::createClient();

        $client->request('GET', '/album/homepokemongo');

        $mainCrawler = $client->getCrawler();

        $this->assertCount(0, $mainCrawler->filter('.album-case-forms'));
    }
}
