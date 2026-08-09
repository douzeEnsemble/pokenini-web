<?php

declare(strict_types=1);

namespace App\Tests\Integration\Controller\Admin;

use App\Controller\AdminUpdateAvailabilitiesController;
use App\Tests\Common\Traits\TestNavTrait;
use App\Tests\Utils\GetUserToken;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Crawler;

/**
 * @internal
 */
#[CoversClass(AdminUpdateAvailabilitiesController::class)]
#[Group('api-mocked-testing')]
final class AdminUpdateAvailabilitiesTest extends WebTestCase
{
    use TestNavTrait;

    public function testUpdateAvailabilitiesNotConnected(): void
    {
        $client = self::createClient();

        $client->request('GET', '/fr/istration/update_availabilities');

        $this->assertResponseStatusCodeSame(307);
    }

    public function testUpdateAvailabilitiesNotAllowed(): void
    {
        $client = self::createClient();

        $user = GetUserToken::getFakeUserToken('8764532', 'TestProvider');
        $client->loginUser($user, 'web');

        $client->request('GET', '/fr/istration/update_availabilities');

        $this->assertResponseStatusCodeSame(403);
    }

    public function testUpdateAvailabilities(): void
    {
        $crawler = $this->getPageConnected();

        $this->assertCountFilter($crawler, 3, '.admin-item-description');
        $this->assertCountFilter($crawler, 3, '.admin-item button.admin-item-cta');
        $this->assertCountFilter($crawler, 0, '.admin-item-cta.disabled');
        $this->assertCountFilter($crawler, 0, '.admin-item-cta[data-confirm-message]');
        $this->assertCountFilter($crawler, 0, '.admin-item-refresh');

        foreach ($this->getExpectedDescriptions() as $itemId => $description) {
            $this->assertSame(
                $description,
                $crawler->filter("#{$itemId} .admin-item-description")->text()
            );
        }

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
            'update_games_availabilities' => 'Resynchronise depuis Google Sheets quels Pokémon sont disponibles dans chaque jeu.',
            'update_games_shinies_availabilities' => 'Resynchronise depuis Google Sheets quels Pokémon chromatiques sont disponibles dans chaque jeu.',
            'update_collections_availabilities' => 'Resynchronise depuis Google Sheets quels Pokémon sont disponibles dans chaque collection.',
        ];
    }

    private function getPageConnected(): Crawler
    {
        $client = self::createClient();

        $user = GetUserToken::getFakeUserToken('8764532', 'TestProvider');
        $user->addAdminRole();
        $client->loginUser($user, 'web');

        $crawler = $client->request('GET', '/fr/istration/update_availabilities');

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
     */
    private function assertReport(
        Crawler $crawler,
        string $item,
        string $type,
        array $expectedReport,
        array $expectedDateTime,
        string $executionTime,
        string $errorMessage = '',
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
     *      error?: string,
     *  },
     *  last?: null|array{
     *      data?: array<string, string>,
     *      datatime: array{
     *          label: string,
     *          value: string,
     *      },
     *      exectime?: string,
     *      error?: string,
     *  },
     * }>
     */
    private function getHomeReportData(): array
    {
        return [
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
        ];
    }
}
