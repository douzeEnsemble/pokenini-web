<?php

namespace App\Tests\Functional\Controller;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class HomeControllerTest extends WebTestCase
{
    public function testHome(): void
    {
        $client = static::createClient();

        $client->request('GET', '/');

        $this->assertResponseIsSuccessful();

        $this->assertHomeNav($client);

        $mainCrawler = $client->getCrawler();

        $firstAlbum = $mainCrawler->filter('.home-item')->first();
        $this->assertEquals('Home', $firstAlbum->text());
        $this->assertEquals('/album/home?lang=fr', $firstAlbum->filter('a')->attr('href'));

        $secondAlbum = $mainCrawler->filter('.home-item')->eq(1);
        $this->assertEquals('Home Chromatique', $secondAlbum->text());
        $this->assertEquals('/album/homeshiny?lang=fr', $secondAlbum->filter('a')->attr('href'));
    }

    public function testHomeFrench(): void
    {
        $client = static::createClient();

        $client->request('GET', '/?lang=fr');

        $this->assertResponseIsSuccessful();

        $this->assertHomeNav($client);

        $mainCrawler = $client->getCrawler();
        $firstAlbum = $mainCrawler->filter('.home-item')->first();
        $this->assertEquals('Home', $firstAlbum->text());
        $this->assertEquals('/album/home?lang=fr', $firstAlbum->filter('a')->attr('href'));

        $secondAlbum = $mainCrawler->filter('.home-item')->eq(1);
        $this->assertEquals('Home Chromatique', $secondAlbum->text());
        $this->assertEquals('/album/homeshiny?lang=fr', $secondAlbum->filter('a')->attr('href'));
    }

    public function testHomeEnglish(): void
    {
        $client = static::createClient();

        $client->request('GET', '/?lang=en');

        $this->assertResponseIsSuccessful();

        $this->assertHomeNav($client);

        $mainCrawler = $client->getCrawler();

        $firstAlbum = $mainCrawler->filter('.home-item')->first();
        $this->assertEquals('Home', $firstAlbum->text());
        $this->assertEquals('/album/home?lang=en', $firstAlbum->filter('a')->attr('href'));

        $secondAlbum = $mainCrawler->filter('.home-item')->eq(1);
        $this->assertEquals('Home Shiny', $secondAlbum->text());
        $this->assertEquals('/album/homeshiny?lang=en', $secondAlbum->filter('a')->attr('href'));
    }

    private function assertHomeNav(KernelBrowser $client): void
    {
        $mainCrawler = $client->getCrawler();

        $this->assertCount(1, $mainCrawler->filter('h1'));
        $this->assertCount(4, $mainCrawler->filter('.home-item'));
        $this->assertCount(4, $mainCrawler->filter('.home-item a'));
    }
}
