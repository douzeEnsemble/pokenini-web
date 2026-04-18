<?php

declare(strict_types=1);

namespace App\Tests\Integration\Controller\Album\Dex;

use App\Controller\AlbumDexListController;
use App\Security\User;
use App\Tests\Common\Traits\TestNavTrait;
use League\OAuth2\Client\Token\AccessToken;
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

        $user = new User('789465465489', 'TestProvider', new AccessToken(['access_token' => sha1('789465465489')]));
        $user->addTrainerRole();
        $client->loginUser($user, 'web');

        $crawler = $client->request('GET', '/fr/album/dex');

        $this->assertResponseIsSuccessful();

        $this->assertFrenchLangSwitch($crawler);

        $this->assertCountFilter($crawler, 6, '.home-item');
        $this->assertCountFilter($crawler, 6, '.home-item h5');
        $this->assertCountFilter($crawler, 1, '.home-item h6');

        $this->assertCountFilter($crawler, 2, '.dex_is_premium');
        $this->assertCountFilter($crawler, 0, '.dex_not_is_released');
        $this->assertCountFilter($crawler, 1, '.dex_is_custom');

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

        $user = new User('0', 'TestProvider', new AccessToken(['access_token' => sha1('0')]));
        $user->addTrainerRole();
        $client->loginUser($user, 'web');

        $crawler = $client->request('GET', '/fr/album/dex');

        $this->assertResponseIsSuccessful();

        $this->assertCountFilter($crawler, 0, '.home-item');
    }

    public function testConnectedHomeDexNoOnHome(): void
    {
        $client = self::createClient();

        $user = new User('1', 'TestProvider', new AccessToken(['access_token' => sha1('1')]));
        $user->addTrainerRole();
        $client->loginUser($user, 'web');

        $crawler = $client->request('GET', '/fr/album/dex');

        $this->assertResponseIsSuccessful();

        $this->assertCountFilter($crawler, 0, '.home-item');
    }

    public function testConnectedHomeSomeDex(): void
    {
        $client = self::createClient();

        $user = new User('2', 'TestProvider', new AccessToken(['access_token' => sha1('2')]));
        $user->addTrainerRole();
        $client->loginUser($user, 'web');

        $crawler = $client->request('GET', '/fr/album/dex');

        $this->assertResponseIsSuccessful();

        $this->assertCountFilter($crawler, 2, '.home-item');
        $this->assertCountFilter($crawler, 2, '.home-item h5');
        $this->assertCountFilter($crawler, 0, '.home-item h6');
    }

    public function testAlbumDexListFrench(): void
    {
        $client = self::createClient();

        $user = new User('789465465489', 'TestProvider', new AccessToken(['access_token' => sha1('789465465489')]));
        $user->addTrainerRole();
        $client->loginUser($user, 'web');

        $crawler = $client->request('GET', '/fr/album/dex?t=7b52009b64fd0a2a49e6d8a939753077792b0554');

        $this->assertResponseIsSuccessful();

        $this->assertFrenchLangSwitch($crawler);

        $this->assertCountFilter($crawler, 6, '.home-item');
        $this->assertCountFilter($crawler, 6, '.home-item h5');
        $this->assertCountFilter($crawler, 1, '.home-item h6');

        $firstAlbum = $crawler->filter('.home-item')->first();
        $this->assertEquals('Épée, Bouclier', $firstAlbum->text());
        $this->assertEquals('/fr/album/swordshield', $firstAlbum->filter('a')->attr('href'));

        $secondAlbum = $crawler->filter('.home-item')->eq(2);
        $this->assertEquals('Home Chromatique', $secondAlbum->text());
        $this->assertEquals('/fr/album/homeshiny', $secondAlbum->filter('a')->attr('href'));
    }

    public function testAlbumDexListEnglish(): void
    {
        $client = self::createClient();

        $user = new User('789465465489', 'TestProvider', new AccessToken(['access_token' => sha1('789465465489')]));
        $user->addTrainerRole();
        $client->loginUser($user, 'web');

        $crawler = $client->request('GET', '/en/album/dex?t=7b52009b64fd0a2a49e6d8a939753077792b0554');

        $this->assertResponseIsSuccessful();

        $this->assertEnglishLangSwitch($crawler);

        $this->assertCountFilter($crawler, 6, '.home-item');
        $this->assertCountFilter($crawler, 6, '.home-item h5');
        $this->assertCountFilter($crawler, 1, '.home-item h6');

        $firstAlbum = $crawler->filter('.home-item')->first();
        $this->assertEquals('Sword, Shield', $firstAlbum->text());
        $this->assertEquals('/en/album/swordshield', $firstAlbum->filter('a')->attr('href'));

        $secondAlbum = $crawler->filter('.home-item')->eq(2);
        $this->assertEquals('Home Shiny', $secondAlbum->text());
        $this->assertEquals('/en/album/homeshiny', $secondAlbum->filter('a')->attr('href'));
    }
}
