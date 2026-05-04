<?php

declare(strict_types=1);

namespace App\Tests\Integration\Controller\Album\Dex;

use App\Controller\AlbumDexListController;
use App\Tests\Common\Traits\TestNavTrait;
use App\Tests\Utils\GetUserToken;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @internal
 */
#[CoversClass(AlbumDexListController::class)]
#[Group('api-mocked-testing')]
final class AlbumDexListTest extends WebTestCase
{
    use TestNavTrait;

    public function testAlbumDexList(): void
    {
        $client = self::createClient();

        $user = GetUserToken::getFakeUserToken();
        $user->addTrainerRole();
        $client->loginUser($user, 'web');

        $crawler = $client->request('GET', '/fr/album/dex');

        $this->assertResponseIsSuccessful();

        $this->assertSame(
            'Pokénini Tes albums',
            $crawler->filter('title')->text()
        );

        $this->assertSame(
            'Tes albums',
            $crawler->filter('h1')->text()
        );

        $this->assertFrenchLangSwitch($crawler);

        $this->assertCountFilter($crawler, 6, '.album-item');
        $this->assertCountFilter($crawler, 6, '.album-item h5');
        $this->assertCountFilter($crawler, 1, '.album-item h6');

        $this->assertCountFilter($crawler, 2, '.dex_is_premium');
        $this->assertCountFilter($crawler, 0, '.dex_not_is_released');
        $this->assertCountFilter($crawler, 1, '.dex_is_custom');

        $firstAlbum = $crawler->filter('.album-item')->first();
        $this->assertEquals('Épée, Bouclier', $firstAlbum->text());
        $this->assertEquals('/fr/album/swordshield', $firstAlbum->filter('a')->attr('href'));
        $this->assertEquals('https://icon.pokenini.fr/banner/swordshield.png', $firstAlbum->filter('img')->attr('src'));

        $secondAlbum = $crawler->filter('.album-item')->eq(2);
        $this->assertEquals('Home Chromatique', $secondAlbum->text());
        $this->assertEquals('/fr/album/homeshiny', $secondAlbum->filter('a')->attr('href'));
        $this->assertEquals('https://icon.pokenini.fr/banner/homeshiny.png', $secondAlbum->filter('img')->attr('src'));

        $this->assertCountFilter($crawler, 0, 'script[src="/js/album.js"]');

        $this->assertStringNotContainsString('const catchStates = JSON.parse', $crawler->outerHtml());
        $this->assertStringNotContainsString('watchCatchStates();', $crawler->outerHtml());
    }

    public function testNonConnectedHome(): void
    {
        $client = self::createClient();

        $client->request('GET', '/fr/album/dex');

        $this->assertTrue($client->getResponse()->isRedirect());
        $this->assertResponseStatusCodeSame(307);

        $crawler = $client->followRedirect();

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertEquals('http://localhost/fr', $crawler->getBaseHref());
    }

    public function testConnectedHomeNoDex(): void
    {
        $client = self::createClient();

        $user = GetUserToken::getFakeUserToken('0', 'TestProvider');
        $user->addTrainerRole();
        $client->loginUser($user, 'web');

        $crawler = $client->request('GET', '/fr/album/dex');

        $this->assertResponseIsSuccessful();

        $this->assertSame(
            'Pokénini Tes albums',
            $crawler->filter('title')->text()
        );

        $this->assertSame(
            'Tes albums',
            $crawler->filter('h1')->text()
        );

        $this->assertCountFilter($crawler, 0, '.album-item');
    }

    public function testConnectedHomeDexNoOnHome(): void
    {
        $client = self::createClient();

        $user = GetUserToken::getFakeUserToken('1', 'TestProvider');
        $user->addTrainerRole();
        $client->loginUser($user, 'web');

        $crawler = $client->request('GET', '/fr/album/dex');

        $this->assertResponseIsSuccessful();

        $this->assertSame(
            'Pokénini Tes albums',
            $crawler->filter('title')->text()
        );

        $this->assertSame(
            'Tes albums',
            $crawler->filter('h1')->text()
        );

        $this->assertCountFilter($crawler, 0, '.album-item');
    }

    public function testConnectedHomeSomeDex(): void
    {
        $client = self::createClient();

        $user = GetUserToken::getFakeUserToken('2', 'TestProvider');
        $user->addTrainerRole();
        $client->loginUser($user, 'web');

        $crawler = $client->request('GET', '/fr/album/dex');

        $this->assertResponseIsSuccessful();

        $this->assertSame(
            'Pokénini Tes albums',
            $crawler->filter('title')->text()
        );

        $this->assertSame(
            'Tes albums',
            $crawler->filter('h1')->text()
        );

        $this->assertCountFilter($crawler, 2, '.album-item');
        $this->assertCountFilter($crawler, 2, '.album-item h5');
        $this->assertCountFilter($crawler, 0, '.album-item h6');
    }

    public function testAlbumDexListFrench(): void
    {
        $client = self::createClient();

        $user = GetUserToken::getFakeUserToken();
        $user->addTrainerRole();
        $client->loginUser($user, 'web');

        $crawler = $client->request('GET', '/fr/album/dex?t=7b52009b64fd0a2a49e6d8a939753077792b0554');

        $this->assertResponseIsSuccessful();

        $this->assertSame(
            'Pokénini Tes albums',
            $crawler->filter('title')->text()
        );

        $this->assertSame(
            'Tes albums',
            $crawler->filter('h1')->text()
        );

        $this->assertFrenchLangSwitch($crawler);

        $this->assertCountFilter($crawler, 6, '.album-item');
        $this->assertCountFilter($crawler, 6, '.album-item h5');
        $this->assertCountFilter($crawler, 1, '.album-item h6');

        $firstAlbum = $crawler->filter('.album-item')->first();
        $this->assertEquals('Épée, Bouclier', $firstAlbum->text());
        $this->assertEquals('/fr/album/swordshield', $firstAlbum->filter('a')->attr('href'));

        $secondAlbum = $crawler->filter('.album-item')->eq(2);
        $this->assertEquals('Home Chromatique', $secondAlbum->text());
        $this->assertEquals('/fr/album/homeshiny', $secondAlbum->filter('a')->attr('href'));
    }

    public function testAlbumDexListEnglish(): void
    {
        $client = self::createClient();

        $user = GetUserToken::getFakeUserToken();
        $user->addTrainerRole();
        $client->loginUser($user, 'web');

        $crawler = $client->request('GET', '/en/album/dex?t=7b52009b64fd0a2a49e6d8a939753077792b0554');

        $this->assertResponseIsSuccessful();

        $this->assertSame(
            'Pokénini Your albums',
            $crawler->filter('title')->text()
        );

        $this->assertSame(
            'Your albums',
            $crawler->filter('h1')->text()
        );

        $this->assertEnglishLangSwitch($crawler);

        $this->assertCountFilter($crawler, 6, '.album-item');
        $this->assertCountFilter($crawler, 6, '.album-item h5');
        $this->assertCountFilter($crawler, 1, '.album-item h6');

        $firstAlbum = $crawler->filter('.album-item')->first();
        $this->assertEquals('Sword, Shield', $firstAlbum->text());
        $this->assertEquals('/en/album/swordshield', $firstAlbum->filter('a')->attr('href'));

        $secondAlbum = $crawler->filter('.album-item')->eq(2);
        $this->assertEquals('Home Shiny', $secondAlbum->text());
        $this->assertEquals('/en/album/homeshiny', $secondAlbum->filter('a')->attr('href'));
    }
}
