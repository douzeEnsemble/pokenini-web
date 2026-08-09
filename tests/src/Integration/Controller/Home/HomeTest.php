<?php

declare(strict_types=1);

namespace App\Tests\Integration\Controller\Home;

use App\Controller\HomeController;
use App\Tests\Common\Traits\TestNavTrait;
use App\Tests\Utils\GetUserToken;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @internal
 */
#[CoversClass(HomeController::class)]
final class HomeTest extends WebTestCase
{
    use TestNavTrait;

    #[Test]
    public function home(): void
    {
        $client = self::createClient();

        $crawler = $client->request('GET', '/fr');

        $this->assertResponseIsSuccessful();

        $this->assertSame(
            'Pokénini',
            $crawler->filter('title')->text()
        );

        $this->assertSame(
            'Pokénini',
            $crawler->filter('h1')->text()
        );

        $this->assertFrenchLangSwitch($crawler);

        $this->assertCountFilter($crawler, 6, '.dex-item');

        $this->assertCountFilter($crawler, 0, 'script[src="/js/album.js"]');

        $this->assertStringNotContainsString('const catchStates = JSON.parse', $crawler->outerHtml());
        $this->assertStringNotContainsString('watchCatchStates();', $crawler->outerHtml());

        $this->assertCountFilter($crawler, 1, '.alert-warning');
        $this->assertCountFilter($crawler, 0, '.alert-light');

        $this->assertCountFilter($crawler, 0, '.home-menu-item');
    }

    #[Test]
    public function homeAsConnectedUser(): void
    {
        $client = self::createClient();

        $user = GetUserToken::getFakeUserToken();
        $user->addTrainerRole();
        $client->loginUser($user, 'web');

        $client->request('GET', '/fr');

        $this->assertResponseRedirects('/fr/album/dex');
    }
}
