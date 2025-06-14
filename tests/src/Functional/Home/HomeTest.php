<?php

declare(strict_types=1);

namespace App\Tests\Functional\Home;

use App\Controller\HomeController;
use App\Security\User;
use App\Tests\Common\Traits\TestNavTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @internal
 */
#[CoversClass(HomeController::class)]
class HomeTest extends WebTestCase
{
    use TestNavTrait;

    public function testHome(): void
    {
        $client = static::createClient();

        $user = new User('789465465489', 'TestProvider');
        $user->addTrainerRole();
        $client->loginUser($user, 'web');

        $crawler = $client->request('GET', '/fr');

        $this->assertResponseIsSuccessful();

        $this->assertFrenchLangSwitch($crawler);

        $this->assertCountFilter($crawler, 0, '.home-item');

        $this->assertCountFilter($crawler, 0, 'script[src="/js/album.js"]');

        $this->assertStringNotContainsString('const catchStates = JSON.parse', $crawler->outerHtml());
        $this->assertStringNotContainsString('watchCatchStates();', $crawler->outerHtml());

        $this->assertCountFilter($crawler, 0, '.alert-warning');
        $this->assertCountFilter($crawler, 1, '.alert-light');
        $this->assertStringContainsString('789465465489', $crawler->filter('.alert-light')->text());

        $this->assertCountFilter($crawler, 2, '.home-menu-item');
        $this->assertEquals('/fr/election/dex', $crawler->filter('.home-menu-item.home-menu-item-election a')->attr('href'));
        $this->assertCountFilter($crawler, 0, '.home-menu-item.home-menu-item-election a.disabled');
        $this->assertEquals('/fr/album/dex', $crawler->filter('.home-menu-item.home-menu-item-album a')->attr('href'));
        $this->assertCountFilter($crawler, 0, '.home-menu-item.home-menu-item-album a.disabled');
    }

    public function testHomeAsAdmin(): void
    {
        $client = static::createClient();

        $user = new User('8764532', 'TestProvider');
        $user->addTrainerRole();
        $user->addAdminRole();
        $client->loginUser($user, 'web');

        $crawler = $client->request('GET', '/fr');

        $this->assertResponseIsSuccessful();

        $this->assertFrenchLangSwitch($crawler);

        $this->assertCountFilter($crawler, 0, '.home-item');

        $this->assertCountFilter($crawler, 0, 'script[src="/js/album.js"]');

        $this->assertStringNotContainsString('const catchStates = JSON.parse', $crawler->outerHtml());
        $this->assertStringNotContainsString('watchCatchStates();', $crawler->outerHtml());

        $this->assertCountFilter($crawler, 0, '.alert-warning');
        $this->assertCountFilter($crawler, 1, '.alert-light');
        $this->assertStringContainsString('8764532', $crawler->filter('.alert-light')->text());

        $this->assertCountFilter($crawler, 2, '.home-menu-item');
        $this->assertEquals('/fr/election/dex', $crawler->filter('.home-menu-item.home-menu-item-election a')->attr('href'));
        $this->assertCountFilter($crawler, 0, '.home-menu-item.home-menu-item-election a.disabled');
        $this->assertEquals('/fr/album/dex', $crawler->filter('.home-menu-item.home-menu-item-album a')->attr('href'));
        $this->assertCountFilter($crawler, 0, '.home-menu-item.home-menu-item-album a.disabled');
    }

    public function testNonConnectedHome(): void
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/fr');

        $this->assertResponseIsSuccessful();

        $this->assertCountFilter($crawler, 0, '.home-item');

        $this->assertCountFilter($crawler, 1, '.alert-warning');
        $this->assertCountFilter($crawler, 0, '.alert-light');

        $this->assertCountFilter($crawler, 2, '.home-menu-item');
        $this->assertEquals('/fr/election/dex', $crawler->filter('.home-menu-item.home-menu-item-election a')->attr('href'));
        $this->assertCountFilter($crawler, 1, '.home-menu-item.home-menu-item-election a.disabled');
        $this->assertEquals('/fr/album/dex', $crawler->filter('.home-menu-item.home-menu-item-album a')->attr('href'));
        $this->assertCountFilter($crawler, 1, '.home-menu-item.home-menu-item-album a.disabled');

        $this->assertCountFilter($crawler, 0, '.home-item');
        $this->assertCountFilter($crawler, 0, '.dex_is_premium');
        $this->assertCountFilter($crawler, 0, '.dex_not_is_released');
        $this->assertCountFilter($crawler, 0, '.dex_is_custom');
    }

    public function testHomeFrench(): void
    {
        $client = static::createClient();

        $user = new User('789465465489', 'TestProvider');
        $user->addTrainerRole();
        $client->loginUser($user, 'web');

        $crawler = $client->request('GET', '/fr?t=7b52009b64fd0a2a49e6d8a939753077792b0554');

        $this->assertResponseIsSuccessful();

        $this->assertFrenchLangSwitch($crawler);

        $this->assertCountFilter($crawler, 0, '.home-item');

        $this->assertCountFilter($crawler, 0, '.alert-warning');
        $this->assertCountFilter($crawler, 1, '.alert-light');
        $this->assertStringContainsString('789465465489', $crawler->filter('.alert-light')->text());

        $this->assertCountFilter($crawler, 2, '.home-menu-item');
        $this->assertEquals('/fr/election/dex', $crawler->filter('.home-menu-item.home-menu-item-election a')->attr('href'));
        $this->assertCountFilter($crawler, 0, '.home-menu-item.home-menu-item-election a.disabled');
        $this->assertEquals('/fr/album/dex', $crawler->filter('.home-menu-item.home-menu-item-album a')->attr('href'));
        $this->assertCountFilter($crawler, 0, '.home-menu-item.home-menu-item-album a.disabled');
    }

    public function testHomeEnglish(): void
    {
        $client = static::createClient();

        $user = new User('789465465489', 'TestProvider');
        $user->addTrainerRole();
        $client->loginUser($user, 'web');

        $crawler = $client->request('GET', '/en?t=7b52009b64fd0a2a49e6d8a939753077792b0554');

        $this->assertResponseIsSuccessful();

        $this->assertEnglishLangSwitch($crawler);

        $this->assertCountFilter($crawler, 0, '.home-item');
    }
}
