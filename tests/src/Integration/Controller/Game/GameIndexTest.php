<?php

declare(strict_types=1);

namespace App\Tests\Integration\Controller\Game;

use App\Controller\GameIndexController;
use App\Tests\Common\Traits\TestNavTrait;
use App\Tests\Utils\GetUserToken;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @internal
 */
#[CoversClass(GameIndexController::class)]
#[Group('api-mocked-testing')]
final class GameIndexTest extends WebTestCase
{
    use TestNavTrait;

    #[Test]
    public function index(): void
    {
        $client = self::createClient();

        $user = GetUserToken::getFakeUserToken();
        $user->addTrainerRole();
        $client->loginUser($user, 'web');

        $crawler = $client->request('GET', '/fr/game');

        $this->assertResponseIsSuccessful();

        $this->assertSame('Pokénini Jeux', $crawler->filter('title')->text());
        $this->assertSame('Jeux', $crawler->filter('h1')->text());
        $this->assertCountFilter($crawler, 1, '.game-item');
        $this->assertSame('/fr/game/color', $crawler->filter('.game-item a')->attr('href'));
        $this->assertSame('Jeu des couleurs', $crawler->filter('.game-item .card-title')->text());

        $this->assertCountFilter($crawler, 1, '.navbar-nav .game-link a.active');
        $this->assertSame('/fr/game', $crawler->filter('.navbar-nav .game-link a')->attr('href'));
    }

    #[Test]
    public function indexEnglish(): void
    {
        $client = self::createClient();

        $user = GetUserToken::getFakeUserToken();
        $user->addTrainerRole();
        $client->loginUser($user, 'web');

        $crawler = $client->request('GET', '/en/game');

        $this->assertResponseIsSuccessful();
        $this->assertSame('Games', $crawler->filter('h1')->text());
        $this->assertSame('Colour game', $crawler->filter('.game-item .card-title')->text());
    }

    #[Test]
    public function indexNotTrainer(): void
    {
        $client = self::createClient();

        $user = GetUserToken::getFakeUserToken();
        $client->loginUser($user, 'web');

        $client->request('GET', '/fr/game');

        $this->assertResponseStatusCodeSame(403);
    }

    #[Test]
    public function indexNotConnected(): void
    {
        $client = self::createClient();

        $client->request('GET', '/fr/game');

        $this->assertResponseRedirects();
    }

    #[Test]
    public function noGameLinkWhenNotTrainer(): void
    {
        $client = self::createClient();

        $crawler = $client->request('GET', '/fr');

        $this->assertResponseIsSuccessful();
        $this->assertCountFilter($crawler, 0, '.navbar-nav .game-link a');
    }
}
