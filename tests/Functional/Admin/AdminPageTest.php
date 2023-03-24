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
        $this->assertCountFilter($crawler, 11, '.admin-item a.admin-item-cta');
        $this->assertCountFilter($crawler, 11, '.admin-item a.admin-item-cta i.bi');

        $this->assertCountFilter($crawler, 5, '.list-group-update .admin-item a.admin-item-cta');
        $this->assertCountFilter($crawler, 2, '.list-group-calculate .admin-item a.admin-item-cta');
        $this->assertCountFilter($crawler, 3ssss, '.list-group-invalidate .admin-item a.admin-item-cta');
        $this->assertCountFilter($crawler, 2, 'table.report-table');
        $this->assertCountFilter($crawler, 1, '.list-group-report-invalidate .admin-item a.admin-item-cta');

        $this->assertCountFilter($crawler, 0, 'script[src="/js/album.js"]');

        $this->assertStringNotContainsString('const catchStates = JSON.parse', $crawler->outerHtml());
        $this->assertStringNotContainsString('watchCatchStates();', $crawler->outerHtml());

        $this->assertReport(
            $crawler,
            'update_labels',
            [
                'Statuts' => '6',
                'Régions' => '0',
                'Catégories' => '6',
                'Formes régionales' => '4',
                'Formes spéciales' => '7',
                'Variantes' => '7',
            ],
            [
                'label' => 'Terminé le',
                'value' => '21/03/2023 09:53:07',
            ]
        );
        $this->assertReport(
            $crawler,
            'update_games_and_dex',
            [],
            [
                'label' => 'Démarré le',
                'value' => '21/03/2023 09:00:20',
            ]
        );
        $this->assertReport(
            $crawler,
            'update_pokemons',
            [
                'Pokémons' => '1 934',
            ],
            [
                'label' => 'Terminé le',
                'value' => '21/03/2023 09:38:03',
            ]
        );
        $this->assertReport(
            $crawler,
            'update_regional_dex_numbers',
            [],
            []
        );
        $this->assertReport(
            $crawler,
            'update_games_availabilities',
            [],
            [
                'label' => 'Terminé le',
                'value' => '21/03/2023 09:25:38',
            ]
        );
        $this->assertReport(
            $crawler,
            'calculate_game_bundles_availabilities',
            [],
            [
                'label' => 'Démarré le',
                'value' => '21/03/2023 07:15:04',
            ]
        );
        $this->assertReport(
            $crawler,
            'calculate_dex_availabilities',
            [
                'Dispo des dex' => '22 472',
            ],
            [
                'label' => 'Terminé le',
                'value' => '21/03/2023 10:05:08',
            ]
        );
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
    }
}
