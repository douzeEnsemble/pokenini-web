<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Api;

use App\DTO\ActionLogData;
use App\Service\Api\GetActionLogsService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class GetActionLogsServiceTest extends TestCase
{
    private ArrayAdapter $cache;

    public function testGet(): void
    {
        $actionLogs = $this->getService()->get();

        $this->assertIsArray($actionLogs);
        $this->assertCount(9, $actionLogs);

        $expectedLogs = [
            'calculate_dex_availabilities',
            'calculate_pokemon_availabilities',
            'calculate_game_bundles_availabilities',
            'calculate_game_bundles_shinies_availabilities',
            'update_games_and_dex',
            'update_games_availabilities',
            'update_games_shinies_availabilities',
            'update_labels',
            'update_pokemons',
        ];
        foreach ($expectedLogs as $key) {
            $this->assertArrayHasKey($key, $actionLogs);
            $this->assertInstanceOf(ActionLogData::class, $actionLogs[$key]);
        }

        $this->assertEmpty($this->cache->getValues());
    }

    private function getService(): GetActionLogsService
    {
        $client = $this->createMock(HttpClientInterface::class);

        $json = (string) file_get_contents('/var/www/html/tests/resources/unit/service/api/action_logs.json');

        $response = $this->createMock(ResponseInterface::class);
        $response
            ->expects($this->once())
            ->method('getContent')
            ->willReturn($json)
        ;

        $client
            ->expects($this->once())
            ->method('request')
            ->with(
                'GET',
                "https://api.domain/action_logs",
                [
                    'headers' => [
                        'accept' => 'application/json',
                    ],
                    'auth_basic' => [
                        'web',
                        'douze',
                    ],
                ],
            )
            ->willReturn($response)
        ;

        $this->cache = new ArrayAdapter();

        return new GetActionLogsService(
            $client,
            'https://api.domain',
            $this->cache,
            'web',
            'douze',
        );
    }
}
