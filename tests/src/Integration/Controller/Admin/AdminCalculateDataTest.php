<?php

declare(strict_types=1);

namespace App\Tests\Integration\Controller\Admin;

use App\Controller\AdminCalculateDataController;
use App\Tests\Common\Traits\TestNavTrait;
use App\Tests\Utils\GetUserToken;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Crawler;

/**
 * @internal
 */
#[CoversClass(AdminCalculateDataController::class)]
#[Group('api-mocked-testing')]
final class AdminCalculateDataTest extends WebTestCase
{
    use TestNavTrait;

    public function testCalculateDataNotConnected(): void
    {
        $client = self::createClient();

        $client->request('GET', '/fr/istration/calculate_data');

        $this->assertResponseStatusCodeSame(307);
    }

    public function testCalculateDataNotAllowed(): void
    {
        $client = self::createClient();

        $user = GetUserToken::getFakeUserToken('8764532', 'TestProvider');
        $client->loginUser($user, 'web');

        $client->request('GET', '/fr/istration/calculate_data');

        $this->assertResponseStatusCodeSame(403);
    }

    /**
     * @SuppressWarnings("PHPMD.ExcessiveMethodLength")
     */
    public function testCalculateData(): void
    {
        $crawler = $this->getPageConnected();

        $this->assertCountFilter($crawler, 4, '.admin-item-description');
        $this->assertCountFilter($crawler, 4, '.admin-item button.admin-item-cta');
        $this->assertCountFilter($crawler, 0, '.admin-item-cta.disabled');

        foreach ($this->getExpectedDescriptions() as $itemId => $description) {
            $this->assertSame(
                $description,
                $crawler->filter("#{$itemId} .admin-item-description")->text()
            );
        }

        $this->assertCountFilter($crawler, 2, '.admin-item-cta[data-confirm-message]');
        $this->assertCountFilter($crawler, 1, '#calculate_game_bundles_availabilities .admin-item-cta[data-confirm-message]');
        $this->assertCountFilter($crawler, 1, '#calculate_dex_availabilities .admin-item-cta[data-confirm-message]');

        $confirmMessage = $crawler->filter('#calculate_game_bundles_availabilities .admin-item-cta')->attr('data-confirm-message') ?? '';
        $this->assertStringContainsString('Une exécution est en cours depuis', $confirmMessage);
        $this->assertStringContainsString('Voulez-vous quand même relancer cette action', $confirmMessage);

        $this->assertCountFilter($crawler, 2, '.admin-item-refresh');

        $this->assertCountFilter($crawler, 1, '#calculate_game_bundles_availabilities .admin-item-refresh');
        $gameBundlesAvailabilitiesHref = $crawler->filter('#calculate_game_bundles_availabilities .admin-item-refresh')->attr('href') ?? '';
        $this->assertStringContainsString('/fr/istration/calculate_data?refresh=', $gameBundlesAvailabilitiesHref);

        $this->assertCountFilter($crawler, 1, '#calculate_dex_availabilities .admin-item-refresh');
        $dexAvailabilitiesHref = $crawler->filter('#calculate_dex_availabilities .admin-item-refresh')->attr('href') ?? '';
        $this->assertStringContainsString('/fr/istration/calculate_data?refresh=', $dexAvailabilitiesHref);

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

        $crawler = $client->request('GET', '/fr/istration/calculate_data');

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
     */
    private function getHomeReportData(): array
    {
        return [
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
