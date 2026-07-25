<?php

declare(strict_types=1);

namespace App\Tests\Integration\Controller\Election;

use App\Controller\ElectionIndexController;
use App\Tests\Common\Traits\TestNavTrait;
use App\Tests\Utils\GetUserToken;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * @internal
 */
#[CoversClass(ElectionIndexController::class)]
#[Group('api-mocked-testing')]
final class ElectionIndexTest extends WebTestCase
{
    use TestNavTrait;

    public function testIndex(): void
    {
        $client = self::createClient();

        $user = GetUserToken::getFakeUserToken();
        $user->addTrainerRole();
        $client->loginUser($user, 'web');

        $crawler = $client->request('GET', '/fr/election/demolite');

        $this->assertResponseIsSuccessful();

        $this->assertSame(
            "Pokénini C'est quoi ton préféré ?",
            $crawler->filter('title')->text()
        );

        $this->assertSame(
            "C'est quoi ton préféré ?",
            $crawler->filter('h1')->text()
        );

        $this->assertSame("C'est quoi ton préféré ?", $crawler->filter('h1')->text());
        $this->assertSame("Choisi d'abord", $crawler->filter('h2')->text());

        $this->assertCountFilter($crawler, 14, '.card');
        $this->assertCountFilter($crawler, 14, '.card-body');
        $this->assertCountFilter($crawler, 12, '.election-card-image-container-regular');
        $this->assertCountFilter($crawler, 0, '.election-card-image-container-shiny');
        $this->assertCountFilter($crawler, 17, '.album-modal-image');
        $this->assertCountFilter($crawler, 0, '.election-card-icon');
        $this->assertCountFilter($crawler, 0, '.election-card-icon-regular');
        $this->assertCountFilter($crawler, 0, '.election-card-icon-shiny');
        $this->assertCountFilter($crawler, 12, 'input[type=checkbox][name="winners_slugs[]"]');

        $this->assertCardContentDemoLite($crawler);
        $this->assertElectionTop($crawler, 'Ton top 5 temporaire');
        $this->assertActions($crawler, "J'ai fait mes choix 0", 'Voir les filtres');
        $this->assertStats(
            $crawler,
            1,
            6,
            17,
            "Tu n'as pas de favoris qui se détache.",
            'exactement 3',
            'info',
        );

        $this->assertSame('Démo, extrait', $crawler->filter('#election-dex-info h4')->text());
        $this->assertSame('41 Pokémons', $crawler->filter('#election-dex-info .dex-pokemon-counter')->text());
        $this->assertSame('', $crawler->filter('#election-dex-info .dex-description')->text());

        $this->assertCountFilter($crawler, 0, '#election-lastpage-toast');
    }

    public function testIndexShinyDex(): void
    {
        $client = self::createClient();

        $user = GetUserToken::getFakeUserToken();
        $user->addTrainerRole();
        $client->loginUser($user, 'web');

        $crawler = $client->request('GET', '/fr/election/demoliteshiny');

        $this->assertResponseIsSuccessful();

        $this->assertSame(
            "Pokénini C'est quoi ton préféré ?",
            $crawler->filter('title')->text()
        );

        $this->assertSame(
            "C'est quoi ton préféré ?",
            $crawler->filter('h1')->text()
        );

        $this->assertSame("C'est quoi ton préféré ?", $crawler->filter('h1')->text());
        $this->assertSame("Choisi d'abord", $crawler->filter('h2')->text());

        $this->assertCountFilter($crawler, 14, '.card');
        $this->assertCountFilter($crawler, 14, '.card-body');
        $this->assertCountFilter($crawler, 0, '.election-card-image-container-regular');
        $this->assertCountFilter($crawler, 12, '.election-card-image-container-shiny');
        $this->assertCountFilter($crawler, 17, '.album-modal-image');
        $this->assertCountFilter($crawler, 0, '.election-card-icon');
        $this->assertCountFilter($crawler, 0, '.election-card-icon-regular');
        $this->assertCountFilter($crawler, 0, '.election-card-icon-shiny');
        $this->assertCountFilter($crawler, 12, 'input[type=checkbox][name="winners_slugs[]"]');

        $this->assertSame('modal', $crawler->filter('#election-modal-welcome')->attr('class'));
        $this->assertSame('modal', $crawler->filter('#election-modal-filters')->attr('class'));
        $this->assertSame('modal', $crawler->filter('#election-modal-filters-advanced')->attr('class'));

        $this->assertCardContentDemoLite($crawler);
        $this->assertElectionTop($crawler, 'Ton top 5 temporaire');
        $this->assertActions($crawler, "J'ai fait mes choix 0", 'Voir les filtres');
        $this->assertStats(
            $crawler,
            7,
            8,
            88,
            "Tu n'as pas de favoris qui se détache.",
            'quasi 6',
            'info',
        );

        $this->assertSame('Démo, extrait chromatique', $crawler->filter('#election-dex-info h4')->text());
        $this->assertSame('41 Pokémons', $crawler->filter('#election-dex-info .dex-pokemon-counter')->text());
        $this->assertSame('', $crawler->filter('#election-dex-info .dex-description')->text());

        $this->assertCountFilter($crawler, 0, '#election-lastpage-toast');
    }

    public function testIndexWithoutDisplayForm(): void
    {
        $client = self::createClient();

        $user = GetUserToken::getFakeUserToken();
        $user->addTrainerRole();
        $client->loginUser($user, 'web');

        $crawler = $client->request('GET', '/fr/election/mega');

        $this->assertResponseIsSuccessful();

        $this->assertSame(
            "Pokénini C'est quoi ton préféré ?",
            $crawler->filter('title')->text()
        );

        $this->assertSame(
            "C'est quoi ton préféré ?",
            $crawler->filter('h1')->text()
        );

        $this->assertSame("C'est quoi ton préféré ?", $crawler->filter('h1')->text());
        $this->assertSame("Choisi d'abord", $crawler->filter('h2')->text());

        $this->assertCountFilter($crawler, 14, '.card');
        $this->assertCountFilter($crawler, 14, '.card-body');
        $this->assertCountFilter($crawler, 12, '.election-card-image-container-regular');
        $this->assertCountFilter($crawler, 0, '.election-card-image-container-shiny');
        $this->assertCountFilter($crawler, 17, '.album-modal-image');
        $this->assertCountFilter($crawler, 0, '.election-card-icon');
        $this->assertCountFilter($crawler, 0, '.election-card-icon-regular');
        $this->assertCountFilter($crawler, 0, '.election-card-icon-shiny');
        $this->assertCountFilter($crawler, 12, 'input[type=checkbox][name="winners_slugs[]"]');

        $this->assertSame('modal', $crawler->filter('#election-modal-welcome')->attr('class'));
        $this->assertSame('modal', $crawler->filter('#election-modal-filters')->attr('class'));
        $this->assertSame('modal', $crawler->filter('#election-modal-filters-advanced')->attr('class'));

        $this->assertCardContentMega($crawler);
        $this->assertElectionTop($crawler, 'Ton top 5 temporaire');
        $this->assertActions($crawler, "J'ai fait mes choix 0", 'Voir les filtres');
        $this->assertStats(
            $crawler,
            5,
            7,
            71,
            "Tu n'as pas de favoris qui se détache.",
            'quasi 3',
            'info',
        );

        $this->assertSame('Méga', $crawler->filter('#election-dex-info h4')->text());
        $this->assertSame('50 Pokémons', $crawler->filter('#election-dex-info .dex-pokemon-counter')->text());
        $this->assertSame(
            'La liste de tous les pokémons ayant une méga ou primo évolution '
            .'dans les différents jeux X, Y ou Soleil et Lune (par exemple).',
            $crawler->filter('#election-dex-info .dex-description')->text()
        );

        $this->assertCountFilter($crawler, 0, '#election-lastpage-toast');
    }

    public function testIndexDetachedCount(): void
    {
        $client = self::createClient();

        $user = GetUserToken::getFakeUserToken();
        $user->addTrainerRole();
        $client->loginUser($user, 'web');

        $crawler = $client->request('GET', '/fr/election/mega/favorite');

        $this->assertResponseIsSuccessful();

        $this->assertSame(
            "Pokénini C'est quoi ton préféré ?",
            $crawler->filter('title')->text()
        );

        $this->assertSame(
            "C'est quoi ton préféré ?",
            $crawler->filter('h1')->text()
        );

        $this->assertSame("C'est quoi ton préféré ?", $crawler->filter('h1')->text());
        $this->assertSame("Choisi d'abord", $crawler->filter('h2')->text());

        $this->assertCountFilter($crawler, 14, '.card');
        $this->assertCountFilter($crawler, 14, '.card-body');
        $this->assertCountFilter($crawler, 12, '.election-card-image-container-regular');
        $this->assertCountFilter($crawler, 0, '.election-card-image-container-shiny');
        $this->assertCountFilter($crawler, 17, '.album-modal-image');
        $this->assertCountFilter($crawler, 0, '.election-card-icon');
        $this->assertCountFilter($crawler, 0, '.election-card-icon-regular');
        $this->assertCountFilter($crawler, 0, '.election-card-icon-shiny');
        $this->assertCountFilter($crawler, 12, 'input[type=checkbox][name="winners_slugs[]"]');

        $this->assertSame('modal', $crawler->filter('#election-modal-welcome')->attr('class'));
        $this->assertSame('modal', $crawler->filter('#election-modal-filters')->attr('class'));
        $this->assertSame('modal', $crawler->filter('#election-modal-filters-advanced')->attr('class'));

        $this->assertCardContentMega($crawler);
        $this->assertElectionTop($crawler, 'Ton top 5 temporaire');
        $this->assertActions($crawler, "J'ai fait mes choix 0", 'Voir les filtres');
        $this->assertStats(
            $crawler,
            7,
            8,
            88,
            'Tu as 1 favori qui se détache',
            'exactement 6',
            'info',
        );

        $this->assertSame('Méga', $crawler->filter('#election-dex-info h4')->text());
        $this->assertSame('50 Pokémons', $crawler->filter('#election-dex-info .dex-pokemon-counter')->text());
        $this->assertSame(
            'La liste de tous les pokémons ayant une méga ou primo évolution '
            .'dans les différents jeux X, Y ou Soleil et Lune (par exemple).',
            $crawler->filter('#election-dex-info .dex-description')->text()
        );

        $this->assertCountFilter($crawler, 0, '#election-lastpage-toast');
    }

    public function testProgressVoteStep(): void
    {
        $client = self::createClient();

        $user = GetUserToken::getFakeUserToken();
        $user->addTrainerRole();
        $client->loginUser($user, 'web');

        $crawler = $client->request('GET', '/fr/election/mega/vote');

        $this->assertResponseIsSuccessful();

        $this->assertSame(
            "Pokénini C'est quoi ton préféré ?",
            $crawler->filter('title')->text()
        );

        $this->assertSame(
            "C'est quoi ton préféré ?",
            $crawler->filter('h1')->text()
        );

        $this->assertSame("C'est quoi ton préféré ?", $crawler->filter('h1')->text());
        $this->assertSame('Maintenant tu votes', $crawler->filter('h2')->text());

        $this->assertCountFilter($crawler, 14, '.card');
        $this->assertCountFilter($crawler, 14, '.card-body');
        $this->assertCountFilter($crawler, 12, '.election-card-image-container-regular');
        $this->assertCountFilter($crawler, 0, '.election-card-image-container-shiny');
        $this->assertCountFilter($crawler, 17, '.album-modal-image');
        $this->assertCountFilter($crawler, 0, '.election-card-icon');
        $this->assertCountFilter($crawler, 0, '.election-card-icon-regular');
        $this->assertCountFilter($crawler, 0, '.election-card-icon-shiny');
        $this->assertCountFilter($crawler, 12, 'input[type=checkbox][name="winners_slugs[]"]');

        $this->assertSame('modal', $crawler->filter('#election-modal-welcome')->attr('class'));
        $this->assertSame('modal', $crawler->filter('#election-modal-filters')->attr('class'));
        $this->assertSame('modal', $crawler->filter('#election-modal-filters-advanced')->attr('class'));

        $this->assertCardContentMega($crawler);
        $this->assertElectionTop($crawler, 'Ton top 5 temporaire');
        $this->assertActions($crawler, "J'ai fait mes choix 0", 'Voir les filtres');
        $this->assertStats(
            $crawler,
            4,
            6,
            67,
            'Tu as 1 favori qui se détache',
            'exactement 2',
            'warning',
        );

        $this->assertSame('Méga', $crawler->filter('#election-dex-info h4')->text());
        $this->assertSame('50 Pokémons', $crawler->filter('#election-dex-info .dex-pokemon-counter')->text());
        $this->assertSame(
            'La liste de tous les pokémons ayant une méga ou primo évolution '
            .'dans les différents jeux X, Y ou Soleil et Lune (par exemple).',
            $crawler->filter('#election-dex-info .dex-description')->text()
        );

        $this->assertCountFilter($crawler, 0, '#election-lastpage-toast');
    }

    public function testProgressLastPageStep(): void
    {
        $client = self::createClient();

        $user = GetUserToken::getFakeUserToken();
        $user->addTrainerRole();
        $client->loginUser($user, 'web');

        $crawler = $client->request('GET', '/fr/election/mega/lastpage');

        $this->assertResponseIsSuccessful();

        $this->assertSame(
            "Pokénini C'est quoi ton préféré ?",
            $crawler->filter('title')->text()
        );

        $this->assertSame(
            "C'est quoi ton préféré ?",
            $crawler->filter('h1')->text()
        );

        $this->assertSame("C'est quoi ton préféré ?", $crawler->filter('h1')->text());
        $this->assertSame("Y'a plus que ceux là", $crawler->filter('h2')->text());

        $this->assertCountFilter($crawler, 13, '.card');
        $this->assertCountFilter($crawler, 13, '.card-body');
        $this->assertCountFilter($crawler, 11, '.election-card-image-container-regular');
        $this->assertCountFilter($crawler, 0, '.election-card-image-container-shiny');
        $this->assertCountFilter($crawler, 16, '.album-modal-image');
        $this->assertCountFilter($crawler, 0, '.election-card-icon');
        $this->assertCountFilter($crawler, 0, '.election-card-icon-regular');
        $this->assertCountFilter($crawler, 0, '.election-card-icon-shiny');
        $this->assertCountFilter($crawler, 11, 'input[type=checkbox][name="winners_slugs[]"]');

        $this->assertSame('modal', $crawler->filter('#election-modal-welcome')->attr('class'));
        $this->assertSame('modal', $crawler->filter('#election-modal-filters')->attr('class'));
        $this->assertSame('modal', $crawler->filter('#election-modal-filters-advanced')->attr('class'));

        $this->assertCardContentMega($crawler);
        $this->assertElectionTop($crawler, 'Ton top 5 temporaire');
        $this->assertActions($crawler, "J'ai fait mes choix 0", 'Voir les filtres');
        $this->assertStats(
            $crawler,
            4,
            8,
            100,
            'Tu as 1 favori qui se détache',
            'presque 4',
            'danger',
        );

        $this->assertSame('Méga', $crawler->filter('#election-dex-info h4')->text());
        $this->assertSame('50 Pokémons', $crawler->filter('#election-dex-info .dex-pokemon-counter')->text());
        $this->assertSame(
            'La liste de tous les pokémons ayant une méga ou primo évolution '
            .'dans les différents jeux X, Y ou Soleil et Lune (par exemple).',
            $crawler->filter('#election-dex-info .dex-description')->text()
        );

        $this->assertSame(
            'Ce sont les 11 derniers. Tu peux en rester là, ou affiner ton choix. Stoi qui décide',
            $crawler->filter('#election-lastpage-toast .toast-body')->text(),
        );
    }

    public function testProgressLastOneStep(): void
    {
        $client = self::createClient();

        $user = GetUserToken::getFakeUserToken();
        $user->addTrainerRole();
        $client->loginUser($user, 'web');

        $crawler = $client->request('GET', '/fr/election/mega/lastone');

        $this->assertResponseIsSuccessful();

        $this->assertSame(
            "Pokénini C'est quoi ton préféré ?",
            $crawler->filter('title')->text()
        );

        $this->assertSame(
            "C'est quoi ton préféré ?",
            $crawler->filter('h1')->text()
        );

        $this->assertSame("C'est quoi ton préféré ?", $crawler->filter('h1')->text());
        $this->assertSame("C'est iel !", $crawler->filter('h2')->text());

        $this->assertCountFilter($crawler, 3, '.card');
        $this->assertCountFilter($crawler, 3, '.card-body');
        $this->assertCountFilter($crawler, 1, '.election-card-image-container-regular');
        $this->assertCountFilter($crawler, 0, '.election-card-image-container-shiny');
        $this->assertCountFilter($crawler, 6, '.album-modal-image');
        $this->assertCountFilter($crawler, 0, '.election-card-icon');
        $this->assertCountFilter($crawler, 0, '.election-card-icon-regular');
        $this->assertCountFilter($crawler, 0, '.election-card-icon-shiny');
        $this->assertCountFilter($crawler, 0, 'input[type=checkbox][name="winners_slugs[]"]');

        $this->assertSame('modal', $crawler->filter('#election-modal-welcome')->attr('class'));
        $this->assertSame('modal', $crawler->filter('#election-modal-filters')->attr('class'));
        $this->assertSame('modal', $crawler->filter('#election-modal-filters-advanced')->attr('class'));

        $this->assertElectionTop($crawler, 'Ton top 5');
        $this->assertCount(1, $crawler->filter('#election-top .pokemon-image-credit'));
        $this->assertActions($crawler, 'Bravo, tu as fini', '');
        $this->assertStats(
            $crawler,
            5,
            7,
            100,
            'Tu as 1 favori qui se détache',
            'quasi 3',
            'success',
        );

        $this->assertSame('Méga', $crawler->filter('#election-dex-info h4')->text());
        $this->assertSame('50 Pokémons', $crawler->filter('#election-dex-info .dex-pokemon-counter')->text());
        $this->assertSame(
            'La liste de tous les pokémons ayant une méga ou primo évolution '
            .'dans les différents jeux X, Y ou Soleil et Lune (par exemple).',
            $crawler->filter('#election-dex-info .dex-description')->text()
        );

        $this->assertCountFilter($crawler, 0, '#election-vote-submit-bottom');
        $this->assertCountFilter($crawler, 0, '#election-lastpage-toast');
    }

    public function testIndexNonTrainer(): void
    {
        $client = self::createClient();

        $user = GetUserToken::getFakeUserToken('8764532', 'TestProvider');
        $client->loginUser($user, 'web');

        $client->catchExceptions(false);

        $this->expectException(AccessDeniedException::class);

        $client->request('GET', '/fr/election/demolite');
    }

    public function testIndexNotFound(): void
    {
        $client = self::createClient();

        $user = GetUserToken::getFakeUserToken();
        $user->addTrainerRole();
        $client->loginUser($user, 'web');

        $client->request('GET', '/fr/election/demolition');

        $this->assertResponseStatusCodeSame(404);
    }

    public function testImageCreditBadgeIsShownOnCandidateCard(): void
    {
        $client = self::createClient();

        $user = GetUserToken::getFakeUserToken();
        $user->addTrainerRole();
        $client->loginUser($user, 'web');

        $client->request('GET', '/fr/election/demolite');

        $crawler = $client->getCrawler();

        $bulbasaurCard = $crawler->filter('#card-bulbasaur');
        $badge = $bulbasaurCard->filter('.pokemon-image-credit');
        $this->assertCount(1, $badge);
        // _candidates.html.twig only ever renders imageMacros.regularPokemonImage/shinyPokemonImage
        // (the "big" image), never the icon macro, so the badge here reflects the big credit,
        // not the small/icon credit used elsewhere (e.g. Album icons).
        $this->assertStringContainsString('PokemonDB', $badge->filter('.visually-hidden')->text());
        $onclick = (string) $badge->first()->attr('onclick');
        $this->assertStringContainsString('window.open(', $onclick);
        $this->assertStringContainsString('pokemondb.net', $onclick);
        $this->assertStringContainsString('sprites', $onclick);
        $this->assertStringContainsString('bulbasaur', $onclick);
    }

    private function assertCardContentDemoLite(Crawler $crawler): void
    {
        $this->assertEquals(
            'Bulbizarre',
            $crawler->filter('#card-bulbasaur .list-group-item')
                ->eq(0)
                ->text()
        );
        $this->assertCountFilter(
            $crawler,
            1,
            '#card-bulbasaur .list-group-item',
            1,
            '.election-card-type-primary.pokemon-type-grass',
        );
        $this->assertCountFilter(
            $crawler,
            1,
            '#card-bulbasaur .list-group-item',
            1,
            '.election-card-type-secondary.pokemon-type-poison',
        );
        $this->assertEquals(
            'bulbasaur',
            $crawler->filter('#card-bulbasaur input[type="checkbox"][name="winners_slugs[]"]')
                ->attr('value')
        );
        $this->assertEquals(
            'bulbasaur',
            $crawler->filter('#card-bulbasaur input[type="hidden"][name="losers_slugs[]"]')
                ->attr('value')
        );
    }

    private function assertCardContentMega(Crawler $crawler): void
    {
        $this->assertEquals(
            'Dracaufeu',
            $crawler->filter('#card-charizard-mega-y .list-group-item')
                ->eq(0)
                ->text()
        );
        $this->assertCountFilter(
            $crawler,
            1,
            '#card-charizard-mega-y .list-group-item',
            1,
            '.election-card-type-primary.pokemon-type-fire',
        );
        $this->assertCountFilter(
            $crawler,
            1,
            '#card-charizard-mega-y .list-group-item',
            1,
            '.election-card-type-secondary.pokemon-type-flying',
        );
        $this->assertEquals(
            'charizard-mega-y',
            $crawler->filter('#card-charizard-mega-y input[type="checkbox"][name="winners_slugs[]"]')
                ->attr('value')
        );
        $this->assertEquals(
            'charizard-mega-y',
            $crawler->filter('#card-charizard-mega-y input[type="hidden"][name="losers_slugs[]"]')
                ->attr('value')
        );
    }

    private function assertElectionTop(Crawler $crawler, string $title): void
    {
        $this->assertCountFilter($crawler, 1, '#election-top');
        $this->assertCountFilter($crawler, 5, '#election-top .election-top-item');
        $this->assertCountFilter($crawler, 5, '#election-top .election-top-item img');
        $this->assertCountFilter($crawler, 5, '#election-top .election-top-item strong');

        $this->assertSame($title, $crawler->filter('#election-top h4')->text());
    }

    private function assertActions(Crawler $crawler, string $actionLabel, string $filterLabel): void
    {
        $this->assertCountFilter($crawler, 1, '#election-actions-top');
        $this->assertCountFilter($crawler, 0, '#election-actions-top .election-actions-item');
        $this->assertCountFilter($crawler, 1, '#election-actions-top .progress');

        $this->assertCountFilter($crawler, 1, '#election-actions-bottom');
        $this->assertCountFilter($crawler, 2, '#election-actions-bottom .election-actions-item');
        $index = 0;
        $this->assertEquals(
            $actionLabel,
            $crawler->filter('#election-actions-bottom .election-actions-item')
                ->eq($index)
                ->text()
        );
        ++$index;
        $this->assertEquals(
            $filterLabel,
            $crawler->filter('#election-actions-bottom .election-actions-item')
                ->eq($index)
                ->text()
        );
    }

    private function assertStats(
        Crawler $crawler,
        int $roundCount,
        int $totalRoundCount,
        int $progress,
        string $favoriteCountText,
        string $almostExactlyText,
        string $progressBarStyle = 'warning',
    ): void {
        $this->assertCountFilter($crawler, 1, '#election-stats');

        $this->assertSame(
            "{$progress}%",
            $crawler->filter('#election-actions-top div.progress')->eq(0)->text()
        );

        $roundsTxt = 1 >= $roundCount ? 'tour' : 'tours';

        $this->assertSame(
            "Tu as fait <strong>{$roundCount}</strong> {$roundsTxt} sur <strong>{$totalRoundCount}</strong>*.",
            $crawler->filter('#election-actions-top div.progress .progress-bar')->eq(0)->attr('data-bs-title')
        );
        $this->assertCountFilter($crawler, 1, "#election-actions-top div.progress .progress-bar.bg-{$progressBarStyle}");

        $this->assertSame(
            "Tu as fait {$roundCount} {$roundsTxt} sur {$totalRoundCount}*.",
            $crawler->filter('#election-stats p span')->eq(0)->text()
        );
        $this->assertSame(
            $favoriteCountText,
            $crawler->filter('#election-stats p')->eq(1)->text()
        );

        $this->assertSame(
            $almostExactlyText,
            $crawler->filter('#election-stats small mark')->eq(0)->text()
        );
    }
}
