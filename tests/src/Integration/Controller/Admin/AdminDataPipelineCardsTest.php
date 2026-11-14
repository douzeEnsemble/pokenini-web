<?php

declare(strict_types=1);

namespace App\Tests\Integration\Controller\Admin;

use App\Controller\AdminDataPipelineController;
use App\Tests\Common\Traits\TestNavTrait;
use App\Tests\Utils\GetUserToken;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Crawler;

/**
 * What the cards of the data pipeline page show: descriptions, buttons, confirmations,
 * refresh links and the last executions reports.
 *
 * @internal
 */
#[CoversClass(AdminDataPipelineController::class)]
#[Group('api-mocked-testing')]
final class AdminDataPipelineCardsTest extends WebTestCase
{
    use TestNavTrait;

    /**
     * @SuppressWarnings("PHPMD.ExcessiveMethodLength")
     */
    public function testCards(): void
    {
        $crawler = $this->getPageConnected();

        $this->assertCountFilter($crawler, 5, '#admin-actions-tab > .nav-item > .nav-link');
        $this->assertCountFilter($crawler, 1, '#admin > #admin-sidebar #admin-sidebar-toggle');
        $this->assertCountFilter($crawler, 1, '#admin > #admin-main');
        $this->assertSame('Réduire le menu', $crawler->filter('#admin-sidebar-toggle')->attr('aria-label'));

        $this->assertCountFilter($crawler, 5, '.navbar-nav .admin-link .dropdown-menu .dropdown-item');
        $this->assertCountFilter($crawler, 1, '.navbar-nav .admin-link .dropdown-item.active');
        $this->assertStringContainsString(
            '/fr/istration/data_pipeline',
            $crawler->filter('.navbar-nav .admin-link .dropdown-item.active')->attr('href') ?? ''
        );
        $this->assertStringContainsString(
            '/fr/istration/reports',
            $crawler->filter('.navbar-nav .admin-link .dropdown-item')->eq(3)->attr('href') ?? ''
        );
        $this->assertStringContainsString(
            '/fr/istration/versions',
            $crawler->filter('.navbar-nav .admin-link .dropdown-item')->eq(4)->attr('href') ?? ''
        );
        $this->assertStringContainsString(
            '/fr/istration/reports',
            $crawler->filter('#admin-actions-tab a.nav-link')->eq(3)->attr('href') ?? ''
        );
        $this->assertStringContainsString(
            '/fr/istration/versions',
            $crawler->filter('#admin-actions-tab a.nav-link')->eq(4)->attr('href') ?? ''
        );

        $this->assertCountFilter($crawler, 11, '.admin-item-description');
        $this->assertCountFilter($crawler, 11, '.admin-item button.admin-item-cta');
        $this->assertCountFilter($crawler, 0, '.admin-item-cta.disabled');

        foreach ($this->getExpectedDescriptions() as $itemId => $description) {
            $this->assertSame(
                $description,
                $crawler->filter("#{$itemId} .admin-item-description")->text()
            );
        }

        $this->assertCountFilter($crawler, 3, '.admin-item-cta[data-confirm-message]');
        foreach (['update_games_collections_and_dex', 'calculate_game_bundles_availabilities', 'calculate_dex_availabilities'] as $pendingItem) {
            $this->assertCountFilter($crawler, 1, "#{$pendingItem} .admin-item-cta[data-confirm-message]");

            $confirmMessage = $crawler->filter("#{$pendingItem} .admin-item-cta")->attr('data-confirm-message') ?? '';
            $this->assertStringContainsString('Une exécution est en cours depuis', $confirmMessage);
            $this->assertStringContainsString('Voulez-vous quand même relancer cette action', $confirmMessage);

            $this->assertCountFilter($crawler, 1, "#{$pendingItem} .admin-item-refresh");
            $href = $crawler->filter("#{$pendingItem} .admin-item-refresh")->attr('href') ?? '';
            $this->assertStringContainsString('/fr/istration/data_pipeline?refresh=', $href);
            $this->assertStringContainsString('#'.$pendingItem, $href);
        }
        $this->assertCountFilter($crawler, 3, '.admin-item-refresh');

        $this->assertCountFilter($crawler, 0, 'script[src^="/js/album.js?v="]');

        $this->assertStringNotContainsString('const catchStates = JSON.parse', $crawler->outerHtml());
        $this->assertStringNotContainsString('watchCatchStates();', $crawler->outerHtml());
        $this->assertStringNotContainsString('const types = JSON.parse', $crawler->outerHtml());

        foreach ($this->getHomeReportData() as $slug => $data) {
            foreach ($data as $type => $report) {
                if (null === $report) {
                    $this->assertNoReport($crawler, $slug, $type);

                    continue;
                }

                $this->assertReport(
                    $crawler,
                    $slug,
                    $type,
                    $report['data'] ?? [],
                    $report['datatime'] ?? [],
                    $report['exectime'] ?? '',
                    $report['error'] ?? '',
                    $report['progress'] ?? false,
                );
            }
        }
    }

    /**
     * @return array<string, string>
     */
    private function getExpectedDescriptions(): array
    {
        return [
            'update_labels' => 'Resynchronise les labels (statuts, types, régions, catégories de dex, catégories, formes régionales, formes spéciales, variantes) depuis la source Google Sheets.',
            'update_games_collections_and_dex' => 'Resynchronise les jeux, les collections et les dex depuis la source Google Sheets.',
            'update_pokemons' => 'Resynchronise les données des Pokémon depuis la source Google Sheets.',
            'update_regional_dex_numbers' => 'Resynchronise les numéros de dex régionaux depuis la source Google Sheets.',
            'update_games_availabilities' => 'Resynchronise depuis Google Sheets quels Pokémon sont disponibles dans chaque jeu.',
            'update_games_shinies_availabilities' => 'Resynchronise depuis Google Sheets quels Pokémon chromatiques sont disponibles dans chaque jeu.',
            'update_collections_availabilities' => 'Resynchronise depuis Google Sheets quels Pokémon sont disponibles dans chaque collection.',
            'calculate_game_bundles_availabilities' => 'Agrège les disponibilités des jeux au niveau des bundles de jeux.',
            'calculate_game_bundles_shinies_availabilities' => 'Agrège les disponibilités chromatiques des jeux au niveau des bundles de jeux.',
            'calculate_dex_availabilities' => 'Agrège les disponibilités des jeux et des collections au niveau des dex.',
            'calculate_pokemon_availabilities' => "Agrège toutes les disponibilités en un résumé par Pokémon, utilisé dans toute l'application.",
        ];
    }

    private function getPageConnected(): Crawler
    {
        $client = self::createClient();

        $user = GetUserToken::getFakeUserToken('8764532', 'TestProvider');
        $user->addAdminRole();
        $client->loginUser($user, 'web');

        $crawler = $client->request('GET', '/fr/istration/data_pipeline');

        $this->assertResponseStatusCodeSame(200);

        $this->assertSame('Pokénini Administration', $crawler->filter('title')->text());
        $this->assertSame('Administration', $crawler->filter('h1')->text());

        $this->assertConnectedNavBar($crawler);
        $this->assertFrenchLangSwitch($crawler);

        return $crawler;
    }

    private function assertNoReport(
        Crawler $crawler,
        string $item,
        string $type,
    ): void {
        $this->assertCountFilter(
            $crawler,
            0,
            ".admin-item-{$item} .admin-item-{$type}"
        );

        $oppositeType = ('current' == $type) ? 'last' : 'current';

        $this->assertCountFilter(
            $crawler,
            0,
            ".admin-item-{$item} .admin-item-{$oppositeType} .admin-item-toggle"
        );
    }

    /**
     * @param array<string, string> $expectedReport
     * @param array<string, string> $expectedDateTime
     *
     * @SuppressWarnings("PHPMD.BooleanArgumentFlag")
     */
    private function assertReport(
        Crawler $crawler,
        string $item,
        string $type,
        array $expectedReport,
        array $expectedDateTime,
        string $executionTime,
        string $errorMessage = '',
        bool $hasProcessBar = false,
    ): void {
        $index = 0;

        $this->assertCountFilter(
            $crawler,
            !$expectedReport ? 0 : 1,
            ".admin-item-{$item} .admin-item-{$type} .admin-item-report"
        );

        foreach ($expectedReport as $label => $value) {
            $this->assertSame(
                $label,
                $crawler->filter(".admin-item-{$item} .admin-item-{$type} .admin-item-report dt")->eq($index)->text()
            );
            $this->assertSame(
                $value,
                $crawler->filter(".admin-item-{$item} .admin-item-{$type} .admin-item-report dd")->eq($index)->text()
            );

            ++$index;
        }

        if ($expectedDateTime) {
            $this->assertCountFilter($crawler, 1, ".admin-item-{$item} .admin-item-{$type} .admin-item-report-date");

            $this->assertSame(
                $expectedDateTime['label'],
                $crawler->filter(".admin-item-{$item} .admin-item-{$type} .admin-item-report-date strong")->text()
            );
            $this->assertSame(
                $expectedDateTime['value'],
                $crawler->filter(".admin-item-{$item} .admin-item-{$type} .admin-item-report-date em")->text()
            );
        }

        if ($executionTime) {
            $this->assertCountFilter($crawler, 1, ".admin-item-{$item} .admin-item-{$type} .admin-item-report-execution");

            $this->assertSame(
                'Terminé en',
                $crawler->filter(".admin-item-{$item} .admin-item-{$type} .admin-item-report-execution strong")->text()
            );
            $this->assertSame(
                $executionTime,
                $crawler->filter(".admin-item-{$item} .admin-item-{$type} .admin-item-report-execution em")->text()
            );
        }

        if ($errorMessage) {
            $this->assertCountFilter($crawler, 1, ".admin-item-{$item} .admin-item-{$type} .alert.alert-danger");

            $this->assertSame(
                $errorMessage,
                $crawler->filter(".admin-item-{$item} .admin-item-{$type} .alert.alert-danger")->text()
            );
        }

        $this->assertCountFilter($crawler, $hasProcessBar ? 1 : 0, ".admin-item-{$item} .admin-item-{$type} .progress");
    }

    /**
     * @return array<string, array{
     *  current: null|array{
     *      data?: array<string, string>,
     *      datatime: array{
     *          label: string,
     *          value: string,
     *      },
     *      exectime?: string,
     *      progress?: bool,
     *      error?: string,
     *  },
     *  last?: null|array{
     *      data?: array<string, string>,
     *      datatime: array{
     *          label: string,
     *          value: string,
     *      },
     *      exectime?: string,
     *      progress?: bool,
     *      error?: string,
     *  },
     * }>
     *
     * @SuppressWarnings("PHPMD.ExcessiveMethodLength")
     */
    private function getHomeReportData(): array
    {
        return [
            'update_labels' => [
                'current' => [
                    'data' => [
                        'Statuts' => '6',
                        'Régions' => '0',
                        'Catégories' => '6',
                        'Formes régionales' => '4',
                        'Formes spéciales' => '7',
                        'Variantes' => '7',
                    ],
                    'datatime' => [
                        'label' => 'Terminé le',
                        'value' => '21/03/2023 13:53:07',
                    ],
                    'exectime' => '00:01:28',
                ],
                'last' => [
                    'data' => [
                        'Statuts' => '5',
                        'Régions' => '0',
                        'Catégories' => '5',
                        'Formes régionales' => '4',
                        'Formes spéciales' => '6',
                        'Variantes' => '6',
                    ],
                    'datatime' => [
                        'label' => 'Terminé le',
                        'value' => '20/03/2023 13:53:07',
                    ],
                    'exectime' => '00:00:08',
                ],
            ],
            'update_games_collections_and_dex' => [
                'current' => [
                    'datatime' => [
                        'label' => 'Démarré le',
                        'value' => '01/09/2023 10:00:20',
                    ],
                    'progress' => true,
                ],
                'last' => [
                    'datatime' => [
                        'label' => 'Terminé le',
                        'value' => '20/04/2023 02:52:59',
                    ],
                    'exectime' => '15:01:59',
                    'error' => 'Exception has been thrown for X reason',
                ],
            ],
            'update_pokemons' => [
                'current' => [
                    'data' => [
                        'Pokémons' => '1 934',
                    ],
                    'datatime' => [
                        'label' => 'Terminé le',
                        'value' => '21/03/2023 10:38:03',
                    ],
                    'exectime' => '00:01:28',
                ],
                'last' => [
                    'data' => [
                        'Pokémons' => '1 930',
                    ],
                    'datatime' => [
                        'label' => 'Terminé le',
                        'value' => '20/03/2023 10:38:03',
                    ],
                    'exectime' => '00:01:18',
                ],
            ],
            'update_regional_dex_numbers' => [
                'current' => null,
                'last' => null,
            ],
            'update_games_availabilities' => [
                'current' => [
                    'datatime' => [
                        'label' => 'Terminé le',
                        'value' => '21/03/2023 10:25:38',
                    ],
                    'exectime' => '00:34:38',
                ],
                'last' => [
                    'datatime' => [
                        'label' => 'Terminé le',
                        'value' => '20/03/2023 20:25:38',
                    ],
                    'exectime' => '00:33:32',
                ],
            ],
            'update_games_shinies_availabilities' => [
                'current' => [
                    'datatime' => [
                        'label' => 'Terminé le',
                        'value' => '22/04/2023 02:52:59',
                    ],
                    'exectime' => '15:01:59',
                    'error' => 'Exception has been thrown for X reason',
                ],
                'last' => [
                    'data' => [
                        'Disponibilités des jeux des chromatiques' => '41 691',
                    ],
                    'datatime' => [
                        'label' => 'Terminé le',
                        'value' => '20/03/2023 10:25:38',
                    ],
                    'exectime' => '00:34:38',
                ],
            ],
            'update_collections_availabilities' => [
                'current' => [
                    'data' => [
                        'Disponibilités des collections' => '1 234',
                    ],
                    'datatime' => [
                        'label' => 'Terminé le',
                        'value' => '21/09/2024 10:35:47',
                    ],
                    'exectime' => '00:01:00',
                ],
                'last' => [
                    'data' => [
                        'Disponibilités des collections' => '312',
                    ],
                    'datatime' => [
                        'label' => 'Terminé le',
                        'value' => '21/09/2024 10:01:00',
                    ],
                    'exectime' => '00:01:00',
                ],
            ],
            'calculate_game_bundles_availabilities' => [
                'current' => [
                    'datatime' => [
                        'label' => 'Démarré le',
                        'value' => '21/03/2023 08:15:04',
                    ],
                ],
                'last' => null,
            ],
            'calculate_game_bundles_shinies_availabilities' => [
                'current' => [
                    'data' => [
                        'Disponibilités des bundles des chromatiques' => '1 234',
                    ],
                    'datatime' => [
                        'label' => 'Terminé le',
                        'value' => '21/04/2023 17:27:18',
                    ],
                    'exectime' => '00:03:00',
                ],
                'last' => [
                    'data' => [
                        'Disponibilités des bundles des chromatiques' => '321',
                    ],
                    'datatime' => [
                        'label' => 'Terminé le',
                        'value' => '20/04/2023 17:28:18',
                    ],
                    'exectime' => '00:03:20',
                ],
            ],
            'calculate_dex_availabilities' => [
                'current' => [
                    'datatime' => [
                        'label' => 'Démarré le',
                        'value' => '21/03/2023 10:14:36',
                    ],
                    'progress' => true,
                ],
                'last' => [
                    'data' => [
                        'Disponibilités des dex' => '22 472',
                    ],
                    'datatime' => [
                        'label' => 'Terminé le',
                        'value' => '20/03/2023 11:05:08',
                    ],
                    'exectime' => '00:50:32',
                ],
            ],
            'calculate_pokemon_availabilities' => [
                'current' => [
                    'data' => [
                        'Disponibilités des packs de jeux par pokémons' => '1',
                        'Disponibilités des chromatiques des packs de jeux par pokémons' => '0',
                    ],
                    'datatime' => [
                        'label' => 'Terminé le',
                        'value' => '14/02/2024 10:14:36',
                    ],
                    'exectime' => '00:00:00',
                ],
                'last' => [
                    'data' => [
                        'Disponibilités des packs de jeux par pokémons' => '1',
                        'Disponibilités des chromatiques des packs de jeux par pokémons' => '0',
                    ],
                    'datatime' => [
                        'label' => 'Terminé le',
                        'value' => '14/02/2024 10:14:36',
                    ],
                    'exectime' => '00:00:00',
                ],
            ],
        ];
    }
}
