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
use Symfony\Component\DomCrawler\Crawler;

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
        $this->assertSame('Des petits jeux autour des Pokémons', $crawler->filter('h2 small')->text());
        $this->assertCountFilter($crawler, 3, '.game-item');
        $this->assertSame(
            ['/fr/game/color', '/fr/game/shiny', '/fr/game/which-shiny'],
            $crawler->filter('.game-item a')->each(static fn (Crawler $node): ?string => $node->attr('href')),
        );
        $this->assertSame(
            ['Jeu des couleurs', 'Shiny or not', 'Lequel est le shiny ?'],
            $crawler->filter('.game-item .card-title')->each(static fn (Crawler $node): string => $node->text()),
        );

        $this->assertCountFilter($crawler, 1, '.navbar-nav .game-link a.active');
        $this->assertSame('/fr/game', $crawler->filter('.navbar-nav .game-link a')->attr('href'));
        $this->assertSame(
            ['/fr/game/color', '/fr/game/shiny', '/fr/game/which-shiny'],
            $crawler->filter('.navbar-nav .game-link .dropdown-item')->each(static fn (Crawler $node): ?string => $node->attr('href')),
        );
        $this->assertSame(
            ['Jeu des couleurs', 'Shiny or not', 'Lequel est le shiny ?'],
            $crawler->filter('.navbar-nav .game-link .dropdown-item')->each(static fn (Crawler $node): string => $node->text()),
        );
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
        $this->assertSame('Colour game', $crawler->filter('.game-item .card-title')->eq(0)->text());
        $this->assertSame(
            'We show you a Pokémon, you guess if it is the regular or the shiny one.',
            $crawler->filter('.game-item .card-text')->eq(1)->text(),
        );
        $this->assertSame(
            'We show you a Pokémon in its regular and shiny versions, you find the shiny one.',
            $crawler->filter('.game-item .card-text')->eq(2)->text(),
        );
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
