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

        $this->assertCountFilter($crawler, 6, '.dex-item');
        $this->assertCountFilter($crawler, 6, '.dex-item h2');
        $this->assertCountFilter($crawler, 1, '.dex-item h3');

        $this->assertCountFilter($crawler, 2, '.dex_is_premium');
        $this->assertCountFilter($crawler, 0, '.dex_not_is_released');
        $this->assertCountFilter($crawler, 1, '.dex_is_custom');

        $firstAlbum = $crawler->filter('.dex-item')->first();
        $this->assertEquals('Épée, Bouclier 12.5% 35%', $firstAlbum->text());
        $this->assertEquals('/fr/album/swordshield', $firstAlbum->filter('a')->attr('href'));
        $this->assertEquals('https://icon.pokenini.fr/banner/swordshield.png', $firstAlbum->filter('img')->attr('src'));

        $secondAlbum = $crawler->filter('.dex-item')->eq(2);
        $this->assertEquals('Home Chromatique 0.66% 99.34%', $secondAlbum->text());
        $this->assertEquals('/fr/album/homeshiny', $secondAlbum->filter('a')->attr('href'));
        $this->assertEquals('https://icon.pokenini.fr/banner/homeshiny.png', $secondAlbum->filter('img')->attr('src'));

        $this->assertCountFilter($crawler, 5, '.dex-item .progress');
        $this->assertCountFilter($crawler, 14, '.dex-item .progress-bar');

        $homeAlbum = $crawler->filter('.dex-item')->eq(1);
        $this->assertEquals('41.72%', $homeAlbum->filter('.progress-bar.catch-state-no')->text());
        $this->assertEquals('58.28%', $homeAlbum->filter('.progress-bar.catch-state-yes')->text());

        $megaAlbum = $crawler->filter('.dex-item')->eq(5);
        $this->assertCountFilter($megaAlbum, 0, '.progress');

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

        $this->assertCountFilter($crawler, 0, '.dex-item');
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

        $this->assertCountFilter($crawler, 0, '.dex-item');
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

        $this->assertCountFilter($crawler, 2, '.dex-item');
        $this->assertCountFilter($crawler, 2, '.dex-item h2');
        $this->assertCountFilter($crawler, 0, '.dex-item h3');
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

        $this->assertCountFilter($crawler, 6, '.dex-item');
        $this->assertCountFilter($crawler, 6, '.dex-item h2');
        $this->assertCountFilter($crawler, 1, '.dex-item h3');

        $firstAlbum = $crawler->filter('.dex-item')->first();
        $this->assertEquals('Épée, Bouclier 12.5% 35%', $firstAlbum->text());
        $this->assertEquals('/fr/album/swordshield', $firstAlbum->filter('a')->attr('href'));

        $secondAlbum = $crawler->filter('.dex-item')->eq(2);
        $this->assertEquals('Home Chromatique 0.66% 99.34%', $secondAlbum->text());
        $this->assertEquals('/fr/album/homeshiny', $secondAlbum->filter('a')->attr('href'));

        $this->assertSame(
            'Prêt à choisir ton Pokémon préféré ?',
            $crawler->filter('#jumbotron h2')->text()
        );
        $this->assertSame(
            'Tu vas pouvoir, enfin, choisir ton Pokémon préféré ! Tu penses peut-être que tu le connais déjà ? Détrompe-toi ! Sache que ce site, testé par des experts dans le domaine, va te surprendre. Es-tu prêt ?!',
            $crawler->filter('#jumbotron p')->text()
        );
        $this->assertCountFilter($crawler, 1, '#jumbotron a[href="/fr/election/dex"]');
        $this->assertCountFilter($crawler, 2, '#jumbotron .jumbotron-close');
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

        $this->assertCountFilter($crawler, 6, '.dex-item');
        $this->assertCountFilter($crawler, 6, '.dex-item h2');
        $this->assertCountFilter($crawler, 1, '.dex-item h3');

        $firstAlbum = $crawler->filter('.dex-item')->first();
        $this->assertEquals('Sword, Shield 12.5% 35%', $firstAlbum->text());
        $this->assertEquals('/en/album/swordshield', $firstAlbum->filter('a')->attr('href'));

        $secondAlbum = $crawler->filter('.dex-item')->eq(2);
        $this->assertEquals('Home Shiny 0.66% 99.34%', $secondAlbum->text());
        $this->assertEquals('/en/album/homeshiny', $secondAlbum->filter('a')->attr('href'));

        $this->assertSame(
            'Ready to pick your favorite Pokémon?',
            $crawler->filter('#jumbotron h2')->text()
        );
        $this->assertSame(
            'Now you can finally choose your favorite Pokémon! Think you already know it? Think again! This site, tested by experts in the field, will surprise you. Are you ready?!',
            $crawler->filter('#jumbotron p')->text()
        );
        $this->assertCountFilter($crawler, 1, '#jumbotron a[href="/en/election/dex"]');
        $this->assertCountFilter($crawler, 2, '#jumbotron .jumbotron-close');
    }

    public function testAlbumDexListCustomDexLinksToUniqueSettingsSlug(): void
    {
        $client = self::createClient();

        $user = GetUserToken::getFakeUserToken('3', 'TestProvider');
        $user->addTrainerRole();
        $client->loginUser($user, 'web');

        $crawler = $client->request('GET', '/fr/album/dex');

        $this->assertResponseIsSuccessful();

        $this->assertCountFilter($crawler, 1, '.dex-item');

        $album = $crawler->filter('.dex-item')->first();

        // The dex's base slug ("homeshiny") is shared with other customizations of the same
        // base dex and is not unique per trainer; only settings.slug ("home-shiny-custom")
        // uniquely identifies this trainer_dex row, so navigation must use it.
        $this->assertEquals('/fr/album/home-shiny-custom', $album->filter('a')->attr('href'));
        $this->assertEquals('https://icon.pokenini.fr/banner/homeshiny.png', $album->filter('img')->attr('src'));
    }
}
