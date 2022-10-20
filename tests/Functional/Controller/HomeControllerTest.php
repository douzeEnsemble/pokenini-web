<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Tests\Common\Traits\TestNavTrait;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class HomeControllerTest extends WebTestCase
{
    use TestNavTrait;

    public function testHome(): void
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/fr/');

        $this->assertResponseIsSuccessful();

        $this->assertNoConnectedNavBar($crawler);
        $this->assertFrenchLangSwitch($crawler);

        $mainCrawler = $client->getCrawler();

        $firstAlbum = $mainCrawler->filter('.home-item')->first();
        $this->assertEquals('Home', $firstAlbum->text());
        $this->assertEquals('/fr/album/r/home', $firstAlbum->filter('a')->attr('href'));

        $secondAlbum = $mainCrawler->filter('.home-item')->eq(1);
        $this->assertEquals('Home Chromatique', $secondAlbum->text());
        $this->assertEquals('/fr/album/r/homeshiny', $secondAlbum->filter('a')->attr('href'));
    }

    public function testHomeFrench(): void
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/fr/');

        $this->assertResponseIsSuccessful();

        $this->assertNoConnectedNavBar($crawler);
        $this->assertFrenchLangSwitch($crawler);

        $mainCrawler = $client->getCrawler();
        $firstAlbum = $mainCrawler->filter('.home-item')->first();
        $this->assertEquals('Home', $firstAlbum->text());
        $this->assertEquals('/fr/album/r/home', $firstAlbum->filter('a')->attr('href'));

        $secondAlbum = $mainCrawler->filter('.home-item')->eq(1);
        $this->assertEquals('Home Chromatique', $secondAlbum->text());
        $this->assertEquals('/fr/album/r/homeshiny', $secondAlbum->filter('a')->attr('href'));
    }

    public function testHomeEnglish(): void
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/en/');

        $this->assertResponseIsSuccessful();

        $this->assertNoConnectedNavBar($crawler);
        $this->assertEnglishLangSwitch($crawler);

        $mainCrawler = $client->getCrawler();

        $firstAlbum = $mainCrawler->filter('.home-item')->first();
        $this->assertEquals('Home', $firstAlbum->text());
        $this->assertEquals('/en/album/r/home', $firstAlbum->filter('a')->attr('href'));

        $secondAlbum = $mainCrawler->filter('.home-item')->eq(1);
        $this->assertEquals('Home Shiny', $secondAlbum->text());
        $this->assertEquals('/en/album/r/homeshiny', $secondAlbum->filter('a')->attr('href'));
    }
}
