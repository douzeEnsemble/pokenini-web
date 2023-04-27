<?php

declare(strict_types=1);

namespace App\Tests\Functional\Admin;

use App\Security\User;
use App\Tests\Common\Traits\TestNavTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Crawler;

class AdminPageTest extends WebTestCase
{
    use TestNavTrait;

    public function testAdminHomeNotConnected(): void
    {
        $client = static::createClient();

        $client->request('GET', '/fr/istration');

        $this->assertResponseStatusCodeSame(307);
    }

    public function testAdminHomeBadCredentials(): void
    {
        $client = static::createClient();

        $client->loginUser(new User('34654656489621361987'));

        $client->request('GET', '/fr/istration');

        $this->assertResponseStatusCodeSame(403);
    }

    public function testAdminHomeNotAllowed(): void
    {
        $client = static::createClient();

        $user = new User('8764532');
        $client->loginUser($user);

        $client->request('GET', '/fr/istration');

        $this->assertResponseStatusCodeSame(403);
    }

    public function testAdminHomeConnected(): void
    {
        $this->getAdminHomeConnected();
    }

    public function testAdminHome(): void
    {
        $crawler = $this->getAdminHomeConnected();

        $this->assertCountFilter($crawler, 2, 'h2');
        $this->assertCountFilter($crawler, 7, 'h3');
        $this->assertCountFilter($crawler, 13, '.admin-item a.admin-item-cta');
        $this->assertCountFilter($crawler, 13, '.admin-item a.admin-item-cta i.bi');

        $this->assertCountFilter($crawler, 6, '.list-group-update .admin-item a.admin-item-cta');
        $this->assertCountFilter($crawler, 3, '.list-group-calculate .admin-item a.admin-item-cta');
        $this->assertCountFilter($crawler, 3, '.list-group-invalidate .admin-item a.admin-item-cta');
        $this->assertCountFilter($crawler, 2, 'table.report-table');
        $this->assertCountFilter($crawler, 1, '.list-group-report-invalidate .admin-item a.admin-item-cta');

        $this->assertCountFilter($crawler, 0, 'script[src="/js/album.js"]');

        $this->assertStringNotContainsString('const catchStates = JSON.parse', $crawler->outerHtml());
        $this->assertStringNotContainsString('watchCatchStates();', $crawler->outerHtml());

        foreach ($this->getHomeReportData() as $slug => $report) {
            $this->assertReport(
                $crawler,
                $slug,
                $report['data'],
                $report['datatime'],
                $report['exectime'][0],
            );
        }
    }

    private function getAdminHomeConnected(): Crawler
    {
        $client = static::createClient();

        $user = new User('8764532');
        $user->addAdminRole();
        $client->loginUser($user);

        $crawler = $client->request('GET', '/fr/istration');

        $this->assertResponseStatusCodeSame(200);

        $this->assertConnectedNavBar($crawler);
        $this->assertFrenchLangSwitch($crawler);

        return $crawler;
    }

    /**
     * @param array<string, string> $expectedReport
     * @param array<string, string> $expectedDateTime
     */
    private function assertReport(
        Crawler $crawler,
        string $item,
        array $expectedReport,
        array $expectedDateTime,
        string $executionTime,
    ): void {
        $index = 0;

        $this->assertCountFilter(
            $crawler,
            ((empty($expectedReport)) ?  0 : 1),
            ".admin-item-$item .admin-item-report"
        );

        foreach ($expectedReport as $label => $value) {
            $this->assertEquals(
                $label,
                $crawler->filter(".admin-item-$item .admin-item-report dt")->eq($index)->text()
            );
            $this->assertEquals(
                $value,
                $crawler->filter(".admin-item-$item .admin-item-report dd")->eq($index)->text()
            );

            $index++;
        }

        if (!empty($expectedDateTime)) {
            $this->assertCountFilter($crawler, 1, ".admin-item-$item .admin-item-report-date");

            $this->assertEquals(
                $expectedDateTime['label'],
                $crawler->filter(".admin-item-$item .admin-item-report-date strong")->text()
            );
            $this->assertEquals(
                $expectedDateTime['value'],
                $crawler->filter(".admin-item-$item .admin-item-report-date em")->text()
            );
        }

        if (!empty($executionTime)) {
            $this->assertCountFilter($crawler, 1, ".admin-item-$item .admin-item-report-execution");

            $this->assertEquals(
                'Terminé en',
                $crawler->filter(".admin-item-$item .admin-item-report-execution strong")->text()
            );
            $this->assertEquals(
                $executionTime,
                $crawler->filter(".admin-item-$item .admin-item-report-execution em")->text()
            );
        }
    }

    /**
     * @return string[][][]
     */
    private function getHomeReportData(): array
    {
        return [
            'update_labels' => [
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
                'exectime' => ['00:01:28'],
            ],
            'update_games_and_dex' => [
                'data' => [],
                'datatime' => [
                    'label' => 'Démarré le',
                    'value' => '21/03/2023 15:00:20',
                ],
                'exectime' => [''],
            ],
            'update_pokemons' => [
                'data' => [
                    'Pokémons' => '1 934',
                ],
                'datatime' => [
                    'label' => 'Terminé le',
                    'value' => '21/03/2023 10:38:03',
                ],
                'exectime' => ['00:01:28'],
            ],
            'update_regional_dex_numbers' => [
                'data' => [],
                'datatime' => [],
                'exectime' => [''],
            ],
            'update_games_availabilities' => [
                'data' => [],
                'datatime' => [
                    'label' => 'Terminé le',
                    'value' => '21/03/2023 10:25:38',
                ],
                'exectime' => ['00:34:38'],
            ],
            'update_games_shinies_availabilities' => [
                'data' => [],
                'datatime' => [
                    'label' => 'Terminé le',
                    'value' => '20/04/2023 02:52:59',
                ],
                'exectime' => ['15:01:59'],
            ],
            'calculate_game_bundles_availabilities' => [
                'data' => [],
                'datatime' => [
                    'label' => 'Démarré le',
                    'value' => '21/03/2023 08:15:04',
                ],
                'exectime' => [''],
            ],
            'calculate_game_bundles_shinies_availabilities' => [
                'data' => [
                    'Dispo des bundles des chromatiques' => '1 234',
                ],
                'datatime' => [
                    'label' => 'Terminé le',
                    'value' => '21/04/2023 17:27:18',
                ],
                'exectime' => ['00:03:00'],
            ],
            'calculate_dex_availabilities' => [
                'data' => [
                    'Dispo des dex' => '22 472',
                ],
                'datatime' => [
                    'label' => 'Terminé le',
                    'value' => '21/03/2023 11:05:08',
                ],
                'exectime' => ['00:50:32'],
            ],
        ];
    }
}
