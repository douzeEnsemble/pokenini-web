<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Api;

use App\Service\Api\GetPokedexService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Adapter\TagAwareAdapter;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * @internal
 */
#[CoversClass(GetPokedexService::class)]
class GetPokedexServiceTest extends TestCase
{
    private ArrayAdapter $cachePool;
    private TagAwareAdapter $cache;

    public function testGet(): void
    {
        $json = (string) file_get_contents(
            '/var/www/html/tests/resources/unit/service/api/pokedex_lite_123.json'
        );

        $pokedex = $this
            ->getService(
                'lite',
                '123',
                $json,
                [],
            )
            ->get(
                'lite',
                '123'
            )
        ;

        $this->assertArrayHasKey('dex', $pokedex);
        $this->assertArrayHasKey('pokemons', $pokedex);
        $this->assertCount(41, $pokedex['pokemons']);
        $this->assertArrayHasKey('report', $pokedex);

        $cacheItem = $this->cache->getItem('album_lite_123');

        /** @var string $value */
        $value = $cacheItem->get();

        $this->assertNotEmpty($value);
        $this->assertJson($value);

        $this->assertSame(
            [
                'album' => 'album',
                'trainer#123' => 'trainer#123',
            ],
            $cacheItem->getMetadata()['tags'],
        );
    }

    public function testGetTwice(): void
    {
        $json = (string) file_get_contents(
            '/var/www/html/tests/resources/unit/service/api/pokedex_lite_123.json'
        );

        $pokedex = $this
            ->getService(
                'lite',
                '123',
                $json,
                [],
            )
            ->get(
                'lite',
                '123'
            )
        ;

        $this->assertArrayHasKey('dex', $pokedex);
        $this->assertArrayHasKey('pokemons', $pokedex);
        $this->assertCount(41, $pokedex['pokemons']);
        $this->assertArrayHasKey('report', $pokedex);

        $cacheItem = $this->cache->getItem('album_lite_123');

        /** @var string $value */
        $value = $cacheItem->get();

        $this->assertNotEmpty($value);
        $this->assertJson($value);

        $this->assertSame(
            [
                'album' => 'album',
                'trainer#123' => 'trainer#123',
            ],
            $cacheItem->getMetadata()['tags'],
        );
    }

    public function testGetWithFilters(): void
    {
        $json = (string) file_get_contents(
            '/var/www/html/tests/resources/unit/service/api/pokedex_lite_123_csyes.json'
        );

        $pokedex = $this
            ->getService(
                'lite',
                '123',
                $json,
                [
                    'catch_states' => [
                        'yes',
                    ],
                ],
            )
            ->get(
                'lite',
                '123',
                [
                    'catch_states' => [
                        'yes',
                    ],
                ],
            )
        ;

        $this->assertArrayHasKey('dex', $pokedex);
        $this->assertArrayHasKey('pokemons', $pokedex);
        $this->assertCount(2, $pokedex['pokemons']);
        $this->assertArrayHasKey('report', $pokedex);

        $cacheItem = $this->cache->getItem('album_lite_123_catch_statesyes');

        /** @var string $value */
        $value = $cacheItem->get();

        $this->assertNotEmpty($value);
        $this->assertJson($value);

        $this->assertSame(
            [
                'album' => 'album',
                'trainer#123' => 'trainer#123',
            ],
            $cacheItem->getMetadata()['tags'],
        );
    }

    public function testGetWithMultiplesFilters(): void
    {
        $json = (string) file_get_contents(
            '/var/www/html/tests/resources/unit/service/api/pokedex_lite_123.json'
        );

        $service = $this->getService(
            'lite',
            '123',
            $json,
            [
                'catch_states' => [
                    'maybe',
                    'maybenot',
                ],
                'any_types' => [
                    'water',
                    'fire',
                    'grass',
                ],
            ],
        );

        $pokedexFirstTime = $service->get(
            'lite',
            '123',
            [
                'catch_states' => [
                    'maybe',
                    'maybenot',
                ],
                'any_types' => [
                    'water',
                    'fire',
                    'grass',
                ],
            ],
        );

        $pokedexLastTime = $service->get(
            'lite',
            '123',
            [
                'catch_states' => [
                    'maybe',
                    'maybenot',
                ],
                'any_types' => [
                    'water',
                    'fire',
                    'grass',
                ],
            ],
        );

        $this->assertArrayHasKey('dex', $pokedexFirstTime);
        $this->assertArrayHasKey('pokemons', $pokedexFirstTime);
        $this->assertArrayHasKey('report', $pokedexFirstTime);

        $this->assertArrayHasKey('dex', $pokedexLastTime);
        $this->assertArrayHasKey('pokemons', $pokedexLastTime);
        $this->assertArrayHasKey('report', $pokedexLastTime);

        $cacheItem = $this->cache->getItem('album_lite_123_catch_statesmaybe_catch_statesmaybenot_any_typeswater_any_typesfire_any_typesgrass');

        /** @var string $value */
        $value = $cacheItem->get();

        $this->assertNotEmpty($value);
        $this->assertJson($value);

        $this->assertSame(
            [
                'album' => 'album',
                'trainer#123' => 'trainer#123',
            ],
            $cacheItem->getMetadata()['tags'],
        );
    }

    public function testGetWithMultiplesNegativeFilters(): void
    {
        $json = (string) file_get_contents(
            '/var/www/html/tests/resources/unit/service/api/pokedex_lite_123.json'
        );

        $pokedex = $this
            ->getService(
                'lite',
                '123',
                $json,
                [
                    'catch_states' => [
                        '!yes',
                    ],
                    'game_bundle_availabilities' => [
                        '!swordshield',
                    ],
                ],
            )
            ->get(
                'lite',
                '123',
                [
                    'catch_states' => [
                        '!yes',
                    ],
                    'game_bundle_availabilities' => [
                        '!swordshield',
                    ],
                ],
            )
        ;

        $this->assertArrayHasKey('dex', $pokedex);
        $this->assertArrayHasKey('pokemons', $pokedex);
        $this->assertCount(41, $pokedex['pokemons']);
        $this->assertArrayHasKey('report', $pokedex);

        $cacheItem = $this->cache->getItem('album_lite_123_catch_states!yes_game_bundle_availabilities!swordshield');

        /** @var string $value */
        $value = $cacheItem->get();

        $this->assertNotEmpty($value);
        $this->assertJson($value);

        $this->assertSame(
            [
                'album' => 'album',
                'trainer#123' => 'trainer#123',
            ],
            $cacheItem->getMetadata()['tags'],
        );
    }

    /**
     * @param string[][]|string[][][] $queryParams
     */
    private function getService(
        string $dexSlug,
        string $trainerId,
        string $json,
        array $queryParams,
    ): GetPokedexService {
        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects($this->exactly(2))
            ->method('info')
        ;

        $client = $this->createMock(HttpClientInterface::class);

        $response = $this->createMock(ResponseInterface::class);
        $response
            ->expects($this->exactly(2))
            ->method('getContent')
            ->willReturn($json)
        ;

        $options = [
            'headers' => [
                'accept' => 'application/json',
            ],
            'auth_basic' => [
                'web',
                'douze',
            ],
            'cafile' => './resources/certificates/cacert.pem',
            'query' => $queryParams,
        ];

        $client
            ->expects($this->once())
            ->method('request')
            ->with(
                'GET',
                "https://api.domain/album/{$trainerId}/{$dexSlug}",
                $options,
            )
            ->willReturn($response)
        ;

        $this->cachePool = new ArrayAdapter();
        $this->cache = new TagAwareAdapter($this->cachePool, new ArrayAdapter());

        return new GetPokedexService(
            $logger,
            $client,
            'https://api.domain',
            './resources/certificates/cacert.pem',
            $this->cache,
            'web',
            'douze',
        );
    }
}
