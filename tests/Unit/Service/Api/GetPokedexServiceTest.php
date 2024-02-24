<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Api;

use App\Service\Api\GetPokedexService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class GetPokedexServiceTest extends TestCase
{
    private ArrayAdapter $cache;

    public function testGet(): void
    {
        $json = (string) file_get_contents(
            "/var/www/html/tests/resources/unit/service/api/pokedex_lite_123.json"
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
            );

        $this->assertArrayHasKey('dex', $pokedex);
        $this->assertArrayHasKey('pokemons', $pokedex);
        $this->assertCount(41, $pokedex['pokemons']);
        $this->assertArrayHasKey('report', $pokedex);

        /** @var string $value */
        $value = $this->cache->getItem('album_lite_123')->get();
        $this->assertNotEmpty($value);
        $this->assertJson($value);

        $register = $this->cache->getItem('register_album')->get();
        $this->assertEquals(
            [
                'album_lite_123'
            ],
            $register
        );
    }

    public function testGetTwice(): void
    {
        $json = (string) file_get_contents(
            "/var/www/html/tests/resources/unit/service/api/pokedex_lite_123.json"
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
            );

        $this->assertArrayHasKey('dex', $pokedex);
        $this->assertArrayHasKey('pokemons', $pokedex);
        $this->assertCount(41, $pokedex['pokemons']);
        $this->assertArrayHasKey('report', $pokedex);

        /** @var string $value */
        $value = $this->cache->getItem('album_lite_123')->get();
        $this->assertNotEmpty($value);
        $this->assertJson($value);

        $register = $this->cache->getItem('register_album')->get();
        $this->assertEquals(
            [
                'album_lite_123'
            ],
            $register
        );
    }

    public function testGetWithFilters(): void
    {
        $json = (string) file_get_contents(
            "/var/www/html/tests/resources/unit/service/api/pokedex_lite_123_csyes.json"
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
            );

        $this->assertArrayHasKey('dex', $pokedex);
        $this->assertArrayHasKey('pokemons', $pokedex);
        $this->assertCount(2, $pokedex['pokemons']);
        $this->assertArrayHasKey('report', $pokedex);

        /** @var string $value */
        $value = $this->cache->getItem('album_lite_123_catch_statesyes')->get();
        $this->assertNotEmpty($value);
        $this->assertJson($value);

        $register = $this->cache->getItem('register_album')->get();
        $this->assertEquals(
            [
                'album_lite_123_catch_statesyes'
            ],
            $register
        );
    }

    public function testGetWithMultiplesFilters(): void
    {
        $json = (string) file_get_contents(
            "/var/www/html/tests/resources/unit/service/api/pokedex_lite_123.json"
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

        /** @var string $value */
        $value = $this->cache->getItem(
            'album_lite_123_catch_statesmaybe_catch_statesmaybenot_any_typeswater_any_typesfire_any_typesgrass'
        )->get();
        $this->assertNotEmpty($value);
        $this->assertJson($value);

        $register = $this->cache->getItem('register_album')->get();
        $this->assertEquals(
            [
                'album_lite_123_catch_statesmaybe_catch_statesmaybenot_any_typeswater_any_typesfire_any_typesgrass'
            ],
            $register
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
        $client = $this->createMock(HttpClientInterface::class);

        $response = $this->createMock(ResponseInterface::class);
        $response
            ->expects($this->once())
            ->method('getContent')
            ->willReturn($json)
        ;

        $options =
        [
            'headers' => [
                'accept' => 'application/json',
            ],
            'auth_basic' => [
                'web',
                'douze',
            ],
            'query' => $queryParams,
        ];

        $client
            ->expects($this->once())
            ->method('request')
            ->with(
                'GET',
                "https://api.domain/album/$trainerId/$dexSlug",
                $options,
            )
            ->willReturn($response)
        ;

        $this->cache = new ArrayAdapter();

        return new GetPokedexService(
            $client,
            'https://api.domain',
            $this->cache,
            'web',
            'douze',
        );
    }
}
