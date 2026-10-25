<?php

declare(strict_types=1);

namespace App\Tests\Integration\Controller\Admin;

use App\Controller\AdminGameReportsController;
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
#[CoversClass(AdminGameReportsController::class)]
#[Group('api-mocked-testing')]
final class AdminGameReportsTest extends WebTestCase
{
    use TestNavTrait;

    #[Test]
    public function gameReports(): void
    {
        $crawler = $this->getPage('fr');

        $this->assertResponseStatusCodeSame(200);

        $this->assertSame('Administration', $crawler->filter('h1')->text());

        $this->assertCountFilter($crawler, 1, '#admin-actions-tab .nav-link.active');
        $this->assertStringContainsString(
            '/fr/istration/reports/games',
            $crawler->filter('#admin-actions-tab .nav-link.active')->attr('href') ?? ''
        );
        $this->assertCountFilter($crawler, 1, '.navbar-nav .admin-link .dropdown-item.active');
        $this->assertStringContainsString(
            '/fr/istration/reports/games',
            $crawler->filter('.navbar-nav .admin-link .dropdown-item.active')->attr('href') ?? ''
        );

        $this->assertCountFilter($crawler, 5, 'table.report-table');
        $this->assertCountFilter($crawler, 1, 'canvas#game_answers');
        $this->assertCountFilter($crawler, 1, 'canvas#game_daily_games');
    }

    #[Test]
    public function gameReportsOverview(): void
    {
        $crawler = $this->getPage('fr');

        $this->assertSame(
            ['', 'Bonne Palette', 'Chroma ou pas', 'Chroma-Trouvé'],
            $crawler->filter('#report-table-games-overview thead th')->each(static fn (Crawler $cell): string => $cell->text()),
        );
        $this->assertCountFilter($crawler, 15, '#report-table-games-overview tbody tr');

        $this->assertOverviewRow($crawler, 'games', 'Parties lancées', ['28', '10', '0']);
        $this->assertOverviewRow($crawler, 'played', 'Parties jouées (au moins une manche)', ['24', '10', '0']);
        $this->assertOverviewRow($crawler, 'players', 'Joueurs', ['4', '3', '0']);
        $this->assertOverviewRow($crawler, 'games_per_player', 'Parties par joueur', ['7.0', '3.3', '-']);
        $this->assertOverviewRow($crawler, 'in_progress', 'Parties en cours', ['4', '1', '0']);
        $this->assertOverviewRow($crawler, 'completed', 'Parties terminées', ['18', '9', '0']);
        $this->assertOverviewRow($crawler, 'abandoned', 'Parties abandonnées', ['6', '0', '0']);
        $this->assertOverviewRow($crawler, 'completion_rate', 'Taux de parties terminées', ['64.3 %', '90.0 %', '-']);
        $this->assertOverviewRow($crawler, 'planned_rounds', 'Manches prévues', ['840', '200', '0']);
        $this->assertOverviewRow($crawler, 'rounds_per_game', 'Manches par partie (moyenne)', ['30.0', '20.0', '-']);
        $this->assertOverviewRow($crawler, 'answered_rounds', 'Manches jouées', ['610', '190', '0']);
        $this->assertOverviewRow($crawler, 'round_completion_rate', 'Taux de complétion des manches', ['72.6 %', '95.0 %', '-']);
        $this->assertOverviewRow($crawler, 'correct', 'Bonnes réponses', ['402', '148', '0']);
        $this->assertOverviewRow($crawler, 'incorrect', 'Mauvaises réponses', ['208', '42', '0']);
        $this->assertOverviewRow($crawler, 'success_rate', 'Pourcentage de réussite', ['65.9 %', '77.9 %', '-']);
    }

    #[Test]
    public function gameReportsByGame(): void
    {
        $crawler = $this->getPage('fr');

        $this->assertSame('Bonne Palette', $crawler->filter('#game-report-color_game h2')->text());
        $this->assertCountFilter($crawler, 2, '#report-table-dex_usage-color_game tbody tr');
        $this->assertSame(
            ['Démo', '16', '4', '360', '66.9 %'],
            $this->getRowTexts($crawler, '#report-table-dex_usage-color_game tbody tr', 0),
        );
        $this->assertCountFilter($crawler, 2, '#report-table-hardest_pokemons-color_game tbody tr');
        $this->assertSame(
            ['Chenipan', '6', '1', '16.7 %'],
            $this->getRowTexts($crawler, '#report-table-hardest_pokemons-color_game tbody tr', 0),
        );
        $this->assertSame(
            'Florizarre Femelle',
            $crawler->filter('#report-table-hardest_pokemons-color_game tbody tr')->eq(1)->filter('th')->text(),
        );

        $this->assertCountFilter($crawler, 1, '#report-table-dex_usage-shiny_game tbody tr');
        $this->assertCountFilter($crawler, 1, '#report-table-hardest_pokemons-shiny_game tbody tr');

        $this->assertCountFilter($crawler, 0, '#game-report-which_shiny_game table');
        $this->assertSame(
            ["Aucune partie pour l'instant.", 'Pas encore assez de réponses (au moins 3 par Pokémon).'],
            $crawler->filter('#game-report-which_shiny_game .game-report-empty')->each(static fn (Crawler $paragraph): string => $paragraph->text()),
        );
        $this->assertCountFilter($crawler, 0, '#game-report-color_game .game-report-empty');
    }

    #[Test]
    public function gameReportsInEnglish(): void
    {
        $crawler = $this->getPage('en');

        $this->assertSame(
            ['', 'Palet Found', 'Shiny or not', 'Spot The Shiny'],
            $crawler->filter('#report-table-games-overview thead th')->each(static fn (Crawler $cell): string => $cell->text()),
        );
        $this->assertSame(
            ['Demo', '16', '4', '360', '66.9 %'],
            $this->getRowTexts($crawler, '#report-table-dex_usage-color_game tbody tr', 0),
        );
        $this->assertSame(
            'Venusaur Female',
            $crawler->filter('#report-table-hardest_pokemons-color_game tbody tr')->eq(1)->filter('th')->text(),
        );
    }

    #[Test]
    public function gameReportsNeedsAdmin(): void
    {
        $client = self::createClient();

        $user = GetUserToken::getFakeUserToken('8764532', 'TestProvider');
        $user->addCollectorRole();
        $client->loginUser($user, 'web');

        $client->request('GET', '/fr/istration/reports/games');

        $this->assertResponseStatusCodeSame(403);
    }

    private function getPage(string $locale): Crawler
    {
        $client = self::createClient();

        $user = GetUserToken::getFakeUserToken('8764532', 'TestProvider');
        $user->addAdminRole();
        $client->loginUser($user, 'web');

        return $client->request('GET', "/{$locale}/istration/reports/games");
    }

    /**
     * @param string[] $values
     */
    private function assertOverviewRow(Crawler $crawler, string $key, string $label, array $values): void
    {
        $row = $crawler->filter("#games-overview-{$key}");

        $this->assertSame($label, $row->filter('th')->text());
        $this->assertSame($values, $row->filter('td')->each(static fn (Crawler $cell): string => $cell->text()));
    }

    /**
     * @return string[]
     */
    private function getRowTexts(Crawler $crawler, string $selector, int $index): array
    {
        return $crawler->filter($selector)->eq($index)->filter('th, td')->each(static fn (Crawler $cell): string => $cell->text());
    }
}
