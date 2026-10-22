<?php

declare(strict_types=1);

namespace App\Tests\Integration\Controller\Game;

use App\Controller\WhichShinyGameController;
use App\Tests\Common\Traits\TestNavTrait;
use App\Tests\Utils\GetUserToken;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Crawler;

/**
 * @internal
 */
#[CoversClass(WhichShinyGameController::class)]
#[Group('api-mocked-testing')]
final class WhichShinyGameDexTest extends WebTestCase
{
    use TestNavTrait;

    #[Test]
    public function dex(): void
    {
        $client = $this->createTrainerClient();

        $crawler = $client->request('GET', '/fr/game/which-shiny');

        $this->assertResponseIsSuccessful();

        $this->assertSame('Pokénini Lequel est le shiny ?', $crawler->filter('title')->text());
        $this->assertSame('Lequel est le shiny ?', $crawler->filter('h1')->text());
        $this->assertCountFilter($crawler, 1, '.navbar-nav .game-link a.nav-link.active');
        $this->assertCountFilter($crawler, 1, '.navbar-nav .game-link .dropdown-item.active');
        $this->assertSame('/fr/game/which-shiny', $crawler->filter('.navbar-nav .game-link .dropdown-item.active')->attr('href'));

        $this->assertCountFilter($crawler, 3, '.dex-item');
        $this->assertSame(
            ['Démo', 'Rouge / Vert / Bleu / Jaune', 'Home Pokémon GO'],
            $crawler->filter('.dex-item .card-title')->each(static fn (Crawler $node): string => $node->text()),
        );
        $this->assertSame('Kanto', $crawler->filter('.dex-item')->eq(1)->filter('.card-subtitle')->text());
        $this->assertSame(
            ['21 Pokémons', '151 Pokémons', '0 Pokémon'],
            $crawler->filter('.dex-item .playable-count')->each(static fn (Crawler $node): string => $node->text()),
        );
        $this->assertCountFilter($crawler, 0, '.dex-item .dex_is_shiny');

        $demo = $crawler->filter('.dex-item')->eq(0);
        $this->assertSame('1 manche jouée sur 3', $demo->filter('.current-game p.small')->eq(0)->text());
        $this->assertSame(
            '/fr/game/which-shiny/11111111-1111-4111-8111-111111111111',
            $demo->filter('a.resume-game')->attr('href'),
        );
        $this->assertSame('Nouvelle partie', $demo->filter('.new-game')->text());
        $this->assertSame('/fr/game/which-shiny', $demo->filter('form')->attr('action'));
        $this->assertSame('demo', $demo->filter('input[name="dex_slug"]')->attr('value'));

        $kanto = $crawler->filter('.dex-item')->eq(1);
        $this->assertCount(0, $kanto->filter('.current-game'));
        $this->assertSame('Jouer', $kanto->filter('.new-game')->text());

        // The number of rounds is chosen before starting
        $this->assertSame('Combien de manches ?', $kanto->filter('.round-count-menu .dropdown-header')->text());
        $this->assertSame(
            ['10 manches', '20 manches', '50 manches'],
            $kanto->filter('button.round-count')->each(static fn (Crawler $node): string => $node->text()),
        );
        $this->assertSame(
            ['10', '20', '50'],
            $kanto->filter('button.round-count')->each(static fn (Crawler $node): string => (string) $node->attr('value')),
        );
        $this->assertSame('Toute la liste (151)', $kanto->filter('button.round-count-all')->text());
        $this->assertSame('', $kanto->filter('button.round-count-all')->attr('value'));
        $this->assertSame('151', $kanto->filter('.round-count-custom input[name="round_count"]')->attr('max'));

        // Only the numbers of rounds below the dex size are offered
        $this->assertSame(
            ['10', '20'],
            $demo->filter('button.round-count')->each(static fn (Crawler $node): string => (string) $node->attr('value')),
        );
        $this->assertSame('Toute la liste (21)', $demo->filter('button.round-count-all')->text());

        $pogo = $crawler->filter('.dex-item')->eq(2);
        $this->assertCount(0, $pogo->filter('form'));
        $this->assertSame('Pas encore de Pokémon dans ce dex.', $pogo->filter('.no-playable')->text());

        $this->assertSame('Tes parties', $crawler->filter('h2.mt-5')->text());
        $this->assertCountFilter($crawler, 3, '.which-shiny-game-history tbody tr');
        $firstRow = $crawler->filter('.which-shiny-game-history tbody tr')->eq(0);
        $this->assertSame(
            ['20/10/2026 10:00', 'Démo', 'En cours'],
            $firstRow->filter('td')->slice(0, 3)->each(static fn (Crawler $node): string => $node->text()),
        );
        $this->assertSame(
            '/fr/game/which-shiny/11111111-1111-4111-8111-111111111111',
            $firstRow->filter('a')->attr('href'),
        );
        $this->assertSame('Terminée', $crawler->filter('.which-shiny-game-history tbody tr')->eq(1)->filter('td')->eq(2)->text());
        $this->assertSame('Abandonnée', $crawler->filter('.which-shiny-game-history tbody tr')->eq(2)->filter('td')->eq(2)->text());
    }

    #[Test]
    public function dexEnglish(): void
    {
        $client = $this->createTrainerClient();

        $crawler = $client->request('GET', '/en/game/which-shiny');

        $this->assertResponseIsSuccessful();

        $this->assertSame('Which one is shiny?', $crawler->filter('h1')->text());
        $this->assertSame('Demo', $crawler->filter('.dex-item .card-title')->eq(0)->text());
        $this->assertSame('No Pokémon in this dex yet.', $crawler->filter('.no-playable')->text());
    }

    #[Test]
    public function dexEmpty(): void
    {
        $client = $this->createTrainerClient('colorgamenone');

        $crawler = $client->request('GET', '/fr/game/which-shiny');

        $this->assertResponseIsSuccessful();

        $this->assertCountFilter($crawler, 0, '.dex-item');
        $this->assertSame("Y'a pas encore de dex pour jouer à Lequel est le shiny.", $crawler->filter('.no-dex')->text());
        $this->assertCountFilter($crawler, 0, '.which-shiny-game-history');
        $this->assertSame("Tu n'as pas encore joué.", $crawler->filter('.no-history')->text());
    }

    #[Test]
    public function dexNotTrainer(): void
    {
        $client = self::createClient();
        $client->loginUser(GetUserToken::getFakeUserToken(), 'web');

        $client->request('GET', '/fr/game/which-shiny');

        $this->assertResponseStatusCodeSame(403);
    }

    private function createTrainerClient(string $identifier = '789465465489'): KernelBrowser
    {
        $client = self::createClient();

        $user = GetUserToken::getFakeUserToken($identifier);
        $user->addTrainerRole();
        $client->loginUser($user, 'web');

        return $client;
    }
}
