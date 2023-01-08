<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Security\User;
use App\Tests\Common\Traits\TestNavTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class HomeControllerTest extends WebTestCase
{
    use TestNavTrait;

    public function testHome(): void
    {
        $client = static::createClient();

        $user = new User('789465465489');
        $user->addTrainerRole();
        $client->loginUser($user);

        $crawler = $client->request('GET', '/fr/');

        $this->assertResponseIsSuccessful();

        $this->assertFrenchLangSwitch($crawler);

        $mainCrawler = $client->getCrawler();

        $this->assertCount(6, $mainCrawler->filter('.home-item'));

        $firstAlbum = $mainCrawler->filter('.home-item')->first();
        $this->assertEquals('Épée, Bouclier', $firstAlbum->text());
        $this->assertEquals('/fr/album/r/swordshield', $firstAlbum->filter('a')->attr('href'));

        $secondAlbum = $mainCrawler->filter('.home-item')->eq(2);
        $this->assertEquals('Home Chromatique', $secondAlbum->text());
        $this->assertEquals('/fr/album/r/homeshiny', $secondAlbum->filter('a')->attr('href'));

        $this->assertCount(0, $mainCrawler->filter('script[src="/js/album_edit.js"]'));

        $this->assertStringNotContainsString('const catchStates = JSON.parse', $mainCrawler->outerHtml());
        $this->assertStringNotContainsString('watchCatchStates();', $mainCrawler->outerHtml());
    }

    public function testNonConnectedHome(): void
    {
        $client = static::createClient();

        $client->request('GET', '/fr/');

        $this->assertResponseIsSuccessful();

        $mainCrawler = $client->getCrawler();

        $this->assertCount(1, $mainCrawler->filter('.home-item'));
        $this->assertCount(1, $mainCrawler->filter('.home-item.login-home-item'));
    }

    public function testConnectedHomeNoDex(): void
    {
        $client = static::createClient();

        $user = new User('0');
        $user->addTrainerRole();
        $client->loginUser($user);

        $client->request('GET', '/fr/');

        $this->assertResponseIsSuccessful();

        $mainCrawler = $client->getCrawler();

        $this->assertCount(0, $mainCrawler->filter('.home-item'));
        $this->assertCount(1, $mainCrawler->filter('.alert'));
    }

    public function testConnectedHomeNoOnHomecDex(): void
    {
        $client = static::createClient();

        $user = new User('1');
        $user->addTrainerRole();
        $client->loginUser($user);

        $client->request('GET', '/fr/');

        $this->assertResponseIsSuccessful();

        $mainCrawler = $client->getCrawler();

        $this->assertCount(0, $mainCrawler->filter('.home-item'));
        $this->assertCount(1, $mainCrawler->filter('.alert'));
        $this->assertCount(1, $mainCrawler->filter('.alert a'));
    }

    public function testConnectedHomeSomeOnHomecDex(): void
    {
        $client = static::createClient();

        $user = new User('2');
        $user->addTrainerRole();
        $client->loginUser($user);

        $client->request('GET', '/fr/');

        $this->assertResponseIsSuccessful();

        $mainCrawler = $client->getCrawler();

        $this->assertCount(2, $mainCrawler->filter('.home-item'));
    }

    public function testHomeFrench(): void
    {
        $client = static::createClient();

        $user = new User('789465465489');
        $user->addTrainerRole();
        $client->loginUser($user);

        $crawler = $client->request('GET', '/fr/?t=7b52009b64fd0a2a49e6d8a939753077792b0554');

        $this->assertResponseIsSuccessful();

        $this->assertFrenchLangSwitch($crawler);

        $mainCrawler = $client->getCrawler();
        $firstAlbum = $mainCrawler->filter('.home-item')->first();
        $this->assertEquals('Épée, Bouclier', $firstAlbum->text());
        $this->assertEquals('/fr/album/r/swordshield', $firstAlbum->filter('a')->attr('href'));

        $secondAlbum = $mainCrawler->filter('.home-item')->eq(2);
        $this->assertEquals('Home Chromatique', $secondAlbum->text());
        $this->assertEquals('/fr/album/r/homeshiny', $secondAlbum->filter('a')->attr('href'));
    }

    public function testHomeEnglish(): void
    {
        $client = static::createClient();

        $user = new User('789465465489');
        $user->addTrainerRole();
        $client->loginUser($user);

        $crawler = $client->request('GET', '/en/?t=7b52009b64fd0a2a49e6d8a939753077792b0554');

        $this->assertResponseIsSuccessful();

        $this->assertEnglishLangSwitch($crawler);

        $mainCrawler = $client->getCrawler();

        $firstAlbum = $mainCrawler->filter('.home-item')->first();
        $this->assertEquals('Sword, Shield', $firstAlbum->text());
        $this->assertEquals('/en/album/r/swordshield', $firstAlbum->filter('a')->attr('href'));

        $secondAlbum = $mainCrawler->filter('.home-item')->eq(2);
        $this->assertEquals('Home Shiny', $secondAlbum->text());
        $this->assertEquals('/en/album/r/homeshiny', $secondAlbum->filter('a')->attr('href'));
    }
}
