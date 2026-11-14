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
 * @internal
 */
#[CoversClass(AdminDataPipelineController::class)]
#[Group('api-mocked-testing')]
final class AdminDataPipelineTest extends WebTestCase
{
    use TestNavTrait;

    public function testNotConnected(): void
    {
        $client = self::createClient();

        $client->request('GET', '/fr/istration/data_pipeline');

        $this->assertResponseStatusCodeSame(307);
    }

    public function testNotAllowed(): void
    {
        $client = self::createClient();

        $client->loginUser(GetUserToken::getFakeUserToken('8764532', 'TestProvider'), 'web');

        $client->request('GET', '/fr/istration/data_pipeline');

        $this->assertResponseStatusCodeSame(403);
    }

    public function testDataPipeline(): void
    {
        $client = self::createClient();

        $user = GetUserToken::getFakeUserToken('8764532', 'TestProvider');
        $user->addAdminRole();
        $client->loginUser($user, 'web');

        $crawler = $client->request('GET', '/fr/istration/data_pipeline');

        $this->assertResponseStatusCodeSame(200);

        $this->assertCountFilter($crawler, 1, '#admin-actions-tab .nav-link.active');
        $this->assertStringContainsString(
            '/fr/istration/data_pipeline',
            $crawler->filter('#admin-actions-tab .nav-link.active')->attr('href') ?? ''
        );

        $this->assertCountFilter($crawler, 11, '.admin-item');
        $this->assertCountFilter($crawler, 1, '#data-pipeline-run-all[data-confirm-message]');
        $this->assertStringContainsString(
            '11 étapes',
            $crawler->filter('#data-pipeline-run-all')->attr('data-confirm-message') ?? ''
        );

        $ids = $crawler->filter('.admin-item')->each(static fn (Crawler $node): string => $node->attr('id') ?? '');
        $this->assertSame(
            [
                'update_labels',
                'update_games_collections_and_dex',
                'update_pokemons',
                'update_regional_dex_numbers',
                'update_games_availabilities',
                'update_games_shinies_availabilities',
                'update_collections_availabilities',
                'calculate_game_bundles_availabilities',
                'calculate_game_bundles_shinies_availabilities',
                'calculate_dex_availabilities',
                'calculate_pokemon_availabilities',
            ],
            $ids,
        );

        $this->assertCountFilter($crawler, 0, '#update_labels .admin-item-dependencies');
        $this->assertCountFilter($crawler, 1, '#calculate_dex_availabilities .admin-item-dependencies');
        $this->assertStringContainsString(
            'Après :',
            $crawler->filter('#calculate_dex_availabilities .admin-item-dependencies')->text()
        );
    }
}
