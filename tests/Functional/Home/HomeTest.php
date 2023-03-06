<?php

declare(strict_types=1);

namespace App\Tests\Functional\Home;

use App\Security\User;
use App\Tests\Common\Traits\TestNavTrait;
use App\Tests\Common\Traits\TestSetUp;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class HomeTest extends WebTestCase
{
    use TestNavTrait;
    use TestSetUp;

    public function testHome(): void
    {
        $client = static::createClient();

        $user = new User('789465465489');
        $user->addTrainerRole();
        $client->loginUser($user);

        $crawler = $client->request('GET', '/fr');

        $this->assertResponseIsSuccessful();

        $this->assertFrenchLangSwitch($crawler);

        $this->assertCountFilter($crawler, 6, '.home-item');

        $firstAlbum = $crawler->filter('.home-item')->first();
        $this->assertEquals('Épée, Bouclier', $firstAlbum->text());
        $this->assertEquals('/fr/album/swordshield', $firstAlbum->filter('a')->attr('href'));
        $this->assertEquals('https://icon.pokenini.fr/banner/swordshield.png', $firstAlbum->filter('img')->attr('src'));

        $secondAlbum = $crawler->filter('.home-item')->eq(2);
        $this->assertEquals('Home Chromatique', $secondAlbum->text());
        $this->assertEquals('/fr/album/homeshiny', $secondAlbum->filter('a')->attr('href'));
        $this->assertEquals('https://icon.pokenini.fr/banner/homeshiny.png', $secondAlbum->filter('img')->attr('src'));

        $this->assertCountFilter($crawler, 0, 'script[src="/js/album.js"]');

        $this->assertStringNotContainsString('const catchStates = JSON.parse', $crawler->outerHtml());
        $this->assertStringNotContainsString('watchCatchStates();', $crawler->outerHtml());
    }

    public function testNonConnectedHome(): void
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/fr');

        $this->assertResponseIsSuccessful();

        $this->assertCountFilter($crawler, 5, '.home-item');

        $this->assertEquals(
            '/fr/connect',
            $crawler->filter('.home-item')->eq(0)->filter('a')->attr('href')
        );
        $this->assertEquals(
            'https://icon.pokenini.fr/banner/not_logged.png',
            $crawler->filter('.home-item')->eq(0)->filter('img')->attr('src')
        );

        $this->assertEquals(
            '/fr/album/home?t=f86cbe805674d85f7806b175b70647a6a9334631',
            $crawler->filter('.home-item')->eq(1)->filter('a')->attr('href')
        );
        $this->assertEquals(
            'https://icon.pokenini.fr/banner/home.png',
            $crawler->filter('.home-item')->eq(1)->filter('img')->attr('src')
        );

        $this->assertEquals(
            '/fr/album/homeshiny?t=f86cbe805674d85f7806b175b70647a6a9334631',
            $crawler->filter('.home-item')->eq(2)->filter('a')->attr('href')
        );
        $this->assertEquals(
            'https://icon.pokenini.fr/banner/homeshiny.png',
            $crawler->filter('.home-item')->eq(2)->filter('img')->attr('src')
        );

        $this->assertEquals(
            '/fr/album/pokemongo?t=f86cbe805674d85f7806b175b70647a6a9334631',
            $crawler->filter('.home-item')->eq(3)->filter('a')->attr('href')
        );
        $this->assertEquals(
            'https://icon.pokenini.fr/banner/pokemongo.png',
            $crawler->filter('.home-item')->eq(3)->filter('img')->attr('src')
        );

        $this->assertEquals(
            '/fr/album/scarletviolet?t=f86cbe805674d85f7806b175b70647a6a9334631',
            $crawler->filter('.home-item')->eq(4)->filter('a')->attr('href')
        );
        $this->assertEquals(
            'https://icon.pokenini.fr/banner/scarletviolet.png',
            $crawler->filter('.home-item')->eq(4)->filter('img')->attr('src')
        );
    }

    public function testConnectedHomeNoDex(): void
    {
        $client = static::createClient();

        $user = new User('0');
        $user->addTrainerRole();
        $client->loginUser($user);

        $crawler = $client->request('GET', '/fr');

        $this->assertResponseIsSuccessful();

        $this->assertCountFilter($crawler, 0, '.home-item');
        $this->assertCountFilter($crawler, 1, '.alert');
    }

    public function testConnectedHomeNoOnHomecDex(): void
    {
        $client = static::createClient();

        $user = new User('1');
        $user->addTrainerRole();
        $client->loginUser($user);

        $crawler = $client->request('GET', '/fr');

        $this->assertResponseIsSuccessful();

        $this->assertCountFilter($crawler, 0, '.home-item');
        $this->assertCountFilter($crawler, 1, '.alert');
        $this->assertCountFilter($crawler, 1, '.alert a');
    }

    public function testConnectedHomeSomeOnHomecDex(): void
    {
        $client = static::createClient();

        $user = new User('2');
        $user->addTrainerRole();
        $client->loginUser($user);

        $crawler = $client->request('GET', '/fr');

        $this->assertResponseIsSuccessful();

        $this->assertCountFilter($crawler, 2, '.home-item');
    }

    public function testHomeFrench(): void
    {
        $client = static::createClient();

        $user = new User('789465465489');
        $user->addTrainerRole();
        $client->loginUser($user);

        $crawler = $client->request('GET', '/fr?t=7b52009b64fd0a2a49e6d8a939753077792b0554');

        $this->assertResponseIsSuccessful();

        $this->assertFrenchLangSwitch($crawler);

        $firstAlbum = $crawler->filter('.home-item')->first();
        $this->assertEquals('Épée, Bouclier', $firstAlbum->text());
        $this->assertEquals('/fr/album/swordshield', $firstAlbum->filter('a')->attr('href'));

        $secondAlbum = $crawler->filter('.home-item')->eq(2);
        $this->assertEquals('Home Chromatique', $secondAlbum->text());
        $this->assertEquals('/fr/album/homeshiny', $secondAlbum->filter('a')->attr('href'));
    }

    public function testHomeEnglish(): void
    {
        $client = static::createClient();

        $user = new User('789465465489');
        $user->addTrainerRole();
        $client->loginUser($user);

        $crawler = $client->request('GET', '/en?t=7b52009b64fd0a2a49e6d8a939753077792b0554');

        $this->assertResponseIsSuccessful();

        $this->assertEnglishLangSwitch($crawler);

        $firstAlbum = $crawler->filter('.home-item')->first();
        $this->assertEquals('Sword, Shield', $firstAlbum->text());
        $this->assertEquals('/en/album/swordshield', $firstAlbum->filter('a')->attr('href'));

        $secondAlbum = $crawler->filter('.home-item')->eq(2);
        $this->assertEquals('Home Shiny', $secondAlbum->text());
        $this->assertEquals('/en/album/homeshiny', $secondAlbum->filter('a')->attr('href'));
    }
}
