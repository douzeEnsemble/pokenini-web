<?php

declare(strict_types=1);

namespace App\Tests\Integration\Controller\Admin;

use App\Controller\AdminUpdateDataController;
use App\Tests\Common\Traits\TestNavTrait;
use App\Tests\Utils\GetUserToken;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Crawler;

/**
 * @internal
 */
#[CoversClass(AdminUpdateDataController::class)]
#[Group('api-mocked-testing')]
final class AdminUpdateDataTest extends WebTestCase
{
    use TestNavTrait;

    public function testUpdateDataNotConnected(): void
    {
        $client = self::createClient();

        $client->request('GET', '/fr/istration/update_data');

        $this->assertResponseStatusCodeSame(307);
    }

    public function testUpdateDataNotAllowed(): void
    {
        $client = self::createClient();

        $user = GetUserToken::getFakeUserToken('8764532', 'TestProvider');
        $client->loginUser($user, 'web');

        $client->request('GET', '/fr/istration/update_data');

        $this->assertResponseStatusCodeSame(403);
    }

    /**
     * @SuppressWarnings("PHPMD.ExcessiveMethodLength")
     */
    public function testUpdateData(): void
    {
        $crawler = $this->getPageConnected();

        $this->assertCountFilter($crawler, 7, '#admin-actions-tab > .nav-item > .nav-link');
        $this->assertCountFilter($crawler, 1, '#admin-actions-tab .nav-link.active');

        $this->assertCountFilter($crawler, 7, '.navbar-nav .admin-link .dropdown-menu .dropdown-item');
        $this->assertCountFilter($crawler, 1, '.navbar-nav .admin-link .dropdown-item.active');
        $this->assertStringContainsString(
            '/fr/istration/update_data',
            $crawler->filter('.navbar-nav .admin-link .dropdown-item.active')->attr('href') ?? ''
        );
        $this->assertStringContainsString(
            '/fr/istration/reports',
            $crawler->filter('.navbar-nav .admin-link .dropdown-item')->eq(5)->attr('href') ?? ''
        );
        $this->assertStringContainsString(
            '/fr/istration/versions',
            $crawler->filter('.navbar-nav .admin-link .dropdown-item')->eq(6)->attr('href') ?? ''
        );

        $reportsLinkHref = $crawler->filter('#admin-actions-tab a.nav-link')->eq(5)->attr('href') ?? '';
        $this->assertStringContainsString('/fr/istration/reports', $reportsLinkHref);

        $versionsLinkHref = $crawler->filter('#admin-actions-tab a.nav-link')->eq(6)->attr('href') ?? '';
        $this->assertStringContainsString('/fr/istration/versions', $versionsLinkHref);

        $this->assertCountFilter($crawler, 4, '.admin-item-description');
        $this->assertCountFilter($crawler, 4, '.admin-item button.admin-item-cta');
        $this->assertCountFilter($crawler, 0, '.admin-item-cta.disabled');

        foreach ($this->getExpectedDescriptions() as $itemId => $description) {
            $this->assertSame(
                $description,
                $crawler->filter("#{$itemId} .admin-item-description")->text()
            );
        }

        $this->assertCountFilter($crawler, 1, '.admin-item-cta[data-confirm-message]');
        $this->assertCountFilter($crawler, 1, '#update_games_collections_and_dex .admin-item-cta[data-confirm-message]');

        $confirmMessage = $crawler->filter('#update_games_collections_and_dex .admin-item-cta')->attr('data-confirm-message') ?? '';
        $this->assertStringContainsString('Une exécution est en cours depuis', $confirmMessage);
        $this->assertStringContainsString('j ', $confirmMessage);
        $this->assertStringContainsString('Voulez-vous quand même relancer cette action', $confirmMessage);

        $this->assertCountFilter($crawler, 1, '.admin-item-refresh');
        $this->assertCountFilter($crawler, 1, '#update_games_collections_and_dex .admin-item-refresh');

        $updateGamesCollectionsAndDexHref = $crawler->filter('#update_games_collections_and_dex .admin-item-refresh')->attr('href') ?? '';
        $this->assertStringContainsString('/fr/istration/update_data?refresh=', $updateGamesCollectionsAndDexHref);
        $this->assertStringContainsString('#update_games_collections_and_dex', $updateGamesCollectionsAndDexHref);

        $this->assertCountFilter($crawler, 0, 'script[src="/js/album.js"]');

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
            'update_labels' => 'Resynchronise les labels (statuts, types, régions, catégories, formes régionales, formes spéciales, variantes) depuis la source Google Sheets.',
            'update_games_collections_and_dex' => 'Resynchronise les jeux, les collections et les dex depuis la source Google Sheets.',
            'update_pokemons' => 'Resynchronise les données des Pokémon depuis la source Google Sheets.',
            'update_regional_dex_numbers' => 'Resynchronise les numéros de dex régionaux depuis la source Google Sheets.',
        ];
    }

    private function getPageConnected(): Crawler
    {
        $client = self::createClient();

        $user = GetUserToken::getFakeUserToken('8764532', 'TestProvider');
        $user->addAdminRole();
        $client->loginUser($user, 'web');

        $crawler = $client->request('GET', '/fr/istration/update_data');

        $this->assertResponseStatusCodeSame(200);

        $this->assertSame(
            'Pokénini Administration',
            $crawler->filter('title')->text()
        );

        $this->assertSame(
            'Administration',
            $crawler->filter('h1')->text()
        );

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
            $this->assertEquals(
                $label,
                $crawler->filter(".admin-item-{$item} .admin-item-{$type} .admin-item-report dt")->eq($index)->text()
            );
            $this->assertEquals(
                $value,
                $crawler->filter(".admin-item-{$item} .admin-item-{$type} .admin-item-report dd")->eq($index)->text()
            );

            ++$index;
        }

        if ($expectedDateTime) {
            $this->assertCountFilter($crawler, 1, ".admin-item-{$item} .admin-item-{$type} .admin-item-report-date");

            $this->assertEquals(
                $expectedDateTime['label'],
                $crawler->filter(".admin-item-{$item} .admin-item-{$type} .admin-item-report-date strong")->text()
            );
            $this->assertEquals(
                $expectedDateTime['value'],
                $crawler->filter(".admin-item-{$item} .admin-item-{$type} .admin-item-report-date em")->text()
            );
        }

        if ($executionTime) {
            $this->assertCountFilter($crawler, 1, ".admin-item-{$item} .admin-item-{$type} .admin-item-report-execution");

            $this->assertEquals(
                'Terminé en',
                $crawler->filter(".admin-item-{$item} .admin-item-{$type} .admin-item-report-execution strong")->text()
            );
            $this->assertEquals(
                $executionTime,
                $crawler->filter(".admin-item-{$item} .admin-item-{$type} .admin-item-report-execution em")->text()
            );
        }

        if ($errorMessage) {
            $this->assertCountFilter($crawler, 1, ".admin-item-{$item} .admin-item-{$type} .alert.alert-danger");

            $this->assertEquals(
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
     *     progress?: bool,
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
        ];
    }
}
