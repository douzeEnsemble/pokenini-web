<?php

declare(strict_types=1);

namespace App\Tests\Integration\Controller\Game;

use App\Controller\ColorGameController;
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
#[CoversClass(ColorGameController::class)]
#[Group('api-mocked-testing')]
final class ColorGameRoundsTest extends WebTestCase
{
    private const string IN_PROGRESS = '11111111-1111-4111-8111-111111111111';
    private const string COMPLETED = '22222222-2222-4222-8222-222222222222';
    private const string ABANDONED = '44444444-4444-4444-8444-444444444444';
    private const string UNKNOWN = '99999999-9999-4999-8999-999999999999';

    #[Test]
    public function rounds(): void
    {
        $client = $this->createTrainerClient();

        $crawler = $client->request('GET', '/fr/game/color/'.self::COMPLETED.'/rounds');

        $this->assertResponseIsSuccessful();

        $this->assertSame('Pokénini Bonne Palette', $crawler->filter('title')->text());
        $this->assertSame('Bonne Palette', $crawler->filter('h1')->text());
        $this->assertSame('Home Chromatique', $crawler->filter('h2 small')->text());
        $this->assertSame('Chromatique', $crawler->filter('h2 .game-is-shiny')->text());
        $this->assertSame('Partie commencée le 20/10/2026 10:00 · Terminée · 1 bonne réponse sur 2', $crawler->filter('.game-rounds-summary')->text());

        // A finished game can be played again, not resumed
        $this->assertCount(0, $crawler->filter('.resume-game'));
        $this->assertSame('/fr/game/color', $crawler->filter('.game-rounds-actions form')->attr('action'));
        $this->assertSame('homeshiny', $crawler->filter('.game-rounds-actions input[name="dex_slug"]')->attr('value'));
        $this->assertSame('Retour à tes parties', $crawler->filter('a.back-to-games')->text());
        $this->assertSame('/fr/game/color', $crawler->filter('a.back-to-games')->attr('href'));

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
            ['Chenipan', 'Bulbizarre'],
            $crawler->filter('.game-round .pokemon-name')->each(static fn (Crawler $node): string => $node->text()),
        );
        $this->assertCount(0, $crawler->filter('.game-round .pokemon-forms'));

        // The colour sets of the round: the right one and the choice of the trainer
        $first = $rounds->eq(0);
        $this->assertSame('https://icon.pokenini.fr/pokemon/big/shiny/caterpie.png', $first->filter('img')->attr('src'));
        $this->assertSame(
            ['btn btn-sm btn-outline-secondary d-flex align-items-center gap-2 color-answer correct-choice', 'btn btn-sm btn-outline-secondary d-flex align-items-center gap-2 color-answer chosen-choice'],
            $first->filter('.color-answer')->each(static fn (Crawler $node): string => (string) $node->attr('class')),
        );
        $this->assertSame(
            ['Les bonnes couleurs', 'Ton choix'],
            $first->filter('.color-answer .visually-hidden')->each(static fn (Crawler $node): string => $node->text()),
        );
        $this->assertSame(
            ['background-color: #F8D030;', 'background-color: #78C850;', 'background-color: #3050F8;', 'background-color: #C878F8;'],
            $first->filter('.color-swatch')->each(static fn (Crawler $node): string => (string) $node->attr('style')),
        );
        $this->assertSame('jaune', $first->filter('.color-swatch')->eq(0)->attr('title'));

        // A right answer: the chosen set is the right one
        $second = $rounds->eq(1);
        $this->assertSame(
            ['btn btn-sm btn-outline-secondary d-flex align-items-center gap-2 color-answer', 'btn btn-sm btn-outline-secondary d-flex align-items-center gap-2 color-answer correct-choice'],
            $second->filter('.color-answer')->each(static fn (Crawler $node): string => (string) $node->attr('class')),
        );
        $this->assertSame('1', $second->filter('.color-answer .choice-key')->eq(0)->text());
    }

    #[Test]
    public function roundsOfAGameInProgress(): void
    {
        $client = $this->createTrainerClient();

        $crawler = $client->request('GET', '/fr/game/color/'.self::IN_PROGRESS.'/rounds');

        $this->assertResponseIsSuccessful();

        $this->assertSame('Partie commencée le 20/10/2026 10:00 · En cours · 1 bonne réponse sur 1', $crawler->filter('.game-rounds-summary')->text());
        $this->assertSame('/fr/game/color/'.self::IN_PROGRESS, $crawler->filter('a.resume-game')->attr('href'));
        $this->assertSame('Continuer', $crawler->filter('a.resume-game')->text());
        $this->assertCount(0, $crawler->filter('.game-rounds-actions form'));

        // Nothing to show with the filter of the wrong rounds
        $this->assertSame('Aucune ratée', $crawler->filter('.game-rounds-filter button[data-filter="wrong"]')->text());
        $this->assertNotNull($crawler->filter('.game-rounds-filter button[data-filter="wrong"]')->attr('disabled'));

        $this->assertCount(1, $crawler->filter('.game-round.is-correct'));
        $this->assertSame('Raichu', $crawler->filter('.game-round .pokemon-name')->text());
        $this->assertSame("Forme d'Alola", $crawler->filter('.game-round .pokemon-forms')->text());
        $this->assertSame('https://icon.pokenini.fr/pokemon/big/regular/raichu-alola.png', $crawler->filter('.game-round img')->attr('src'));
        $this->assertCount(3, $crawler->filter('.game-round .color-answer'));
        $this->assertCount(0, $crawler->filter('.game-round .game-is-shiny'));
    }

    #[Test]
    public function roundsWithoutAnsweredRound(): void
    {
        $client = $this->createTrainerClient();

        $crawler = $client->request('GET', '/fr/game/color/'.self::ABANDONED.'/rounds');

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

        $crawler = $client->request('GET', '/en/game/color/'.self::COMPLETED.'/rounds');

        $this->assertResponseIsSuccessful();

        $this->assertSame('Palet Found', $crawler->filter('h1')->text());
        $this->assertSame('Home Shiny', $crawler->filter('h2 small')->text());
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
            ['Caterpie', 'Bulbasaur'],
            $crawler->filter('.game-round .pokemon-name')->each(static fn (Crawler $node): string => $node->text()),
        );
        $this->assertSame('Back to your games', $crawler->filter('a.back-to-games')->text());
    }

    #[Test]
    public function roundsOfAnUnknownGame(): void
    {
        $client = $this->createTrainerClient();

        $client->request('GET', '/fr/game/color/'.self::UNKNOWN.'/rounds');

        $this->assertResponseStatusCodeSame(404);
    }

    #[Test]
    public function roundsNotAnUuid(): void
    {
        $client = $this->createTrainerClient();

        $client->request('GET', '/fr/game/color/douze/rounds');

        $this->assertResponseStatusCodeSame(404);
    }

    #[Test]
    public function roundsNotTrainer(): void
    {
        $client = self::createClient();
        $client->loginUser(GetUserToken::getFakeUserToken(), 'web');

        $client->request('GET', '/fr/game/color/'.self::COMPLETED.'/rounds');

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
