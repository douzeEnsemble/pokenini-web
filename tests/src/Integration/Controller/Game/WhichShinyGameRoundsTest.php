<?php

declare(strict_types=1);

namespace App\Tests\Integration\Controller\Game;

use App\Controller\WhichShinyGameController;
use App\Tests\Utils\GetUserToken;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Crawler;

/**
 * The answered rounds of a game, to look at it again.
 *
 * @internal
 */
#[CoversClass(WhichShinyGameController::class)]
#[Group('api-mocked-testing')]
final class WhichShinyGameRoundsTest extends WebTestCase
{
    private const string IN_PROGRESS = '11111111-1111-4111-8111-111111111111';
    private const string COMPLETED = '22222222-2222-4222-8222-222222222222';
    private const string ABANDONED = '44444444-4444-4444-8444-444444444444';
    private const string UNKNOWN = '99999999-9999-4999-8999-999999999999';

    #[Test]
    public function rounds(): void
    {
        $client = $this->createTrainerClient();

        $crawler = $client->request('GET', '/fr/game/which-shiny/'.self::COMPLETED.'/rounds');

        $this->assertResponseIsSuccessful();

        $this->assertSame('Pokénini Chroma-Trouvé', $crawler->filter('title')->text());
        $this->assertSame('Chroma-Trouvé', $crawler->filter('h1')->text());
        $this->assertSame('Rouge / Vert / Bleu / Jaune', $crawler->filter('h2 small')->text());
        $this->assertSame('Partie commencée le 20/10/2026 10:00 · Terminée · 1 bonne réponse sur 2', $crawler->filter('.game-rounds-summary')->text());

        // A finished game can be played again, not resumed
        $this->assertCount(0, $crawler->filter('.resume-game'));
        $this->assertSame('/fr/game/which-shiny', $crawler->filter('.game-rounds-actions form')->attr('action'));
        $this->assertSame('redgreenblueyellow', $crawler->filter('.game-rounds-actions input[name="dex_slug"]')->attr('value'));
        $this->assertSame('Retour à tes parties', $crawler->filter('a.back-to-games')->text());
        $this->assertSame('/fr/game/which-shiny', $crawler->filter('a.back-to-games')->attr('href'));

        $this->assertSame(
            ['Toutes (2)', '1 ratée'],
            $crawler->filter('.game-rounds-filter button')->each(static fn (Crawler $node): string => $node->text()),
        );
        $this->assertNull($crawler->filter('.game-rounds-filter button[data-filter="wrong"]')->attr('disabled'));

        $rounds = $crawler->filter('.game-round');
        $this->assertCount(2, $rounds);
        $this->assertSame(
            ['col game-round is-wrong', 'col game-round is-correct'],
            $rounds->each(static fn (Crawler $node): string => (string) $node->attr('class')),
        );
        $this->assertSame(
            ['Manche 1', 'Manche 2'],
            $crawler->filter('.game-round .round-position')->each(static fn (Crawler $node): string => $node->text()),
        );
        $this->assertSame(
            ['Raté', 'Bien vu'],
            $crawler->filter('.game-round .round-result')->each(static fn (Crawler $node): string => $node->text()),
        );
        $this->assertSame(
            ['Chenipan', 'Chrysacier'],
            $crawler->filter('.game-round .pokemon-name')->each(static fn (Crawler $node): string => $node->text()),
        );
        $this->assertCount(0, $crawler->filter('.game-round .pokemon-forms'));

        // The two images in their order, the shiny one and the choice of the trainer
        $first = $rounds->eq(0);
        $this->assertSame(
            ['https://icon.pokenini.fr/pokemon/big/shiny/caterpie.png', 'https://icon.pokenini.fr/pokemon/big/regular/caterpie.png'],
            $first->filter('img')->each(static fn (Crawler $node): string => (string) $node->attr('src')),
        );
        $this->assertSame(
            ['Chromatique', 'Normal'],
            $first->filter('.version-name')->each(static fn (Crawler $node): string => $node->text()),
        );
        $this->assertCount(1, $first->filter('figure')->eq(0)->filter('.bi-stars'));
        $this->assertSame('h-100 m-0 p-2 border rounded-3 d-flex flex-column align-items-center gap-1 which-shiny-version shiny-version', $first->filter('figure')->eq(0)->attr('class'));
        $this->assertSame('h-100 m-0 p-2 border rounded-3 d-flex flex-column align-items-center gap-1 which-shiny-version chosen-version', $first->filter('figure')->eq(1)->attr('class'));
        $this->assertCount(0, $first->filter('figure')->eq(0)->filter('.version-label'));
        $this->assertSame('Ton choix', $first->filter('figure')->eq(1)->filter('.version-label')->text());
        $this->assertSame('badge text-bg-danger version-label', $first->filter('figure')->eq(1)->filter('.version-label')->attr('class'));

        $second = $rounds->eq(1);
        $this->assertSame(
            ['https://icon.pokenini.fr/pokemon/big/regular/metapod.png', 'https://icon.pokenini.fr/pokemon/big/shiny/metapod.png'],
            $second->filter('img')->each(static fn (Crawler $node): string => (string) $node->attr('src')),
        );
        $this->assertSame('h-100 m-0 p-2 border rounded-3 d-flex flex-column align-items-center gap-1 which-shiny-version', $second->filter('figure')->eq(0)->attr('class'));
        $this->assertSame('badge text-bg-success version-label', $second->filter('figure')->eq(1)->filter('.version-label')->attr('class'));
    }

    #[Test]
    public function roundsOfAGameInProgress(): void
    {
        $client = $this->createTrainerClient();

        $crawler = $client->request('GET', '/fr/game/which-shiny/'.self::IN_PROGRESS.'/rounds');

        $this->assertResponseIsSuccessful();

        $this->assertSame('Partie commencée le 20/10/2026 10:00 · En cours · 1 bonne réponse sur 1', $crawler->filter('.game-rounds-summary')->text());
        $this->assertSame('/fr/game/which-shiny/'.self::IN_PROGRESS, $crawler->filter('a.resume-game')->attr('href'));
        $this->assertSame('Continuer', $crawler->filter('a.resume-game')->text());
        $this->assertCount(0, $crawler->filter('.game-rounds-actions form'));

        // Nothing to show with the filter of the wrong rounds
        $this->assertSame('Aucune ratée', $crawler->filter('.game-rounds-filter button[data-filter="wrong"]')->text());
        $this->assertNotNull($crawler->filter('.game-rounds-filter button[data-filter="wrong"]')->attr('disabled'));

        $this->assertCount(1, $crawler->filter('.game-round.is-correct'));
        $this->assertSame('Raichu', $crawler->filter('.game-round .pokemon-name')->text());
        $this->assertSame("Forme d'Alola", $crawler->filter('.game-round .pokemon-forms')->text());
        $this->assertSame('Ton choix', $crawler->filter('.game-round figure')->eq(1)->filter('.version-label')->text());
    }

    #[Test]
    public function roundsWithoutAnsweredRound(): void
    {
        $client = $this->createTrainerClient();

        $crawler = $client->request('GET', '/fr/game/which-shiny/'.self::ABANDONED.'/rounds');

        $this->assertResponseIsSuccessful();

        $this->assertSame('Aucune manche jouée dans cette partie.', $crawler->filter('.no-rounds')->text());
        $this->assertCount(0, $crawler->filter('.game-rounds-filter'));
        $this->assertCount(0, $crawler->filter('.game-round'));
        $this->assertCount(1, $crawler->filter('.game-rounds-actions .new-game'));
    }

    #[Test]
    public function roundsEnglish(): void
    {
        $client = $this->createTrainerClient();

        $crawler = $client->request('GET', '/en/game/which-shiny/'.self::COMPLETED.'/rounds');

        $this->assertResponseIsSuccessful();

        $this->assertSame('Spot The Shiny', $crawler->filter('h1')->text());
        $this->assertSame('Red / Green / Blue / Yellow', $crawler->filter('h2 small')->text());
        $this->assertSame('Game started on 20/10/2026 10:00 · Completed · 1 right answer out of 2', $crawler->filter('.game-rounds-summary')->text());
        $this->assertSame(
            ['All (2)', '1 missed'],
            $crawler->filter('.game-rounds-filter button')->each(static fn (Crawler $node): string => $node->text()),
        );
        $this->assertSame(
            ['Round 1', 'Round 2'],
            $crawler->filter('.game-round .round-position')->each(static fn (Crawler $node): string => $node->text()),
        );
        $this->assertSame(
            ['Missed', 'Well spotted'],
            $crawler->filter('.game-round .round-result')->each(static fn (Crawler $node): string => $node->text()),
        );
        $this->assertSame(
            ['Caterpie', 'Metapod'],
            $crawler->filter('.game-round .pokemon-name')->each(static fn (Crawler $node): string => $node->text()),
        );
        $this->assertSame('Back to your games', $crawler->filter('a.back-to-games')->text());
    }

    #[Test]
    public function roundsOfAnUnknownGame(): void
    {
        $client = $this->createTrainerClient();

        $client->request('GET', '/fr/game/which-shiny/'.self::UNKNOWN.'/rounds');

        $this->assertResponseStatusCodeSame(404);
    }

    #[Test]
    public function roundsNotAnUuid(): void
    {
        $client = $this->createTrainerClient();

        $client->request('GET', '/fr/game/which-shiny/douze/rounds');

        $this->assertResponseStatusCodeSame(404);
    }

    #[Test]
    public function roundsNotTrainer(): void
    {
        $client = self::createClient();
        $client->loginUser(GetUserToken::getFakeUserToken(), 'web');

        $client->request('GET', '/fr/game/which-shiny/'.self::COMPLETED.'/rounds');

        $this->assertResponseStatusCodeSame(403);
    }

    private function createTrainerClient(): KernelBrowser
    {
        $client = self::createClient();

        $user = GetUserToken::getFakeUserToken();
        $user->addTrainerRole();
        $client->loginUser($user, 'web');

        return $client;
    }
}
