<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Back;

use App\Service\Back\GetPokedexService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(GetPokedexService::class)]
class GetPokedexServiceTest extends TestCase
{
    use BackServiceTrait;

    public function testGet(): void
    {
        $pokedex = $this
            ->getService(
                'lite',
                '123',
                'pokedex_lite_123.json',
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
    }

    public function testGetTwice(): void
    {
        $pokedex = $this
            ->getService(
                'lite',
                '123',
                'pokedex_lite_123.json',
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
    }

    public function testGetWithFilters(): void
    {
        $pokedex = $this
            ->getService(
                'lite',
                '123',
                'pokedex_lite_123_csyes.json',
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
    }

    public function testGetWithMultiplesFilters(): void
    {
        $service = $this->getService(
            'lite',
            '123',
            'pokedex_lite_123.json',
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

        $pokedex = $service->get(
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

        $this->assertArrayHasKey('dex', $pokedex);
        $this->assertArrayHasKey('pokemons', $pokedex);
        $this->assertArrayHasKey('report', $pokedex);
    }

    public function testGetWithMultiplesNegativeFilters(): void
    {
        $pokedex = $this
            ->getService(
                'lite',
                '123',
                'pokedex_lite_123.json',
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
    }

    public function testGetWithoutLoggedUser(): void
    {
        /** @var GetPokedexService $service */
        $service = $this->getServiceWithoutLoggedUser(
            GetPokedexService::class,
            'GET',
            (string) file_get_contents('/var/www/html/tests/resources/unit/service/back/pokedex_lite_123.json'),
            'album/123/lite',
            [
                'query' => [],
            ],
        );

        $pokedex = $service->get('lite', '123');

        $this->assertArrayHasKey('dex', $pokedex);
        $this->assertArrayHasKey('pokemons', $pokedex);
        $this->assertCount(41, $pokedex['pokemons']);
        $this->assertArrayHasKey('report', $pokedex);
    }

    /**
     * @param string[][]|string[][][] $queryParams
     */
    private function getService(
        string $dexSlug,
        string $trainerId,
        string $filename,
        array $queryParams,
    ): GetPokedexService {
        /** @var GetPokedexService */
        return $this->getServiceWithLoggedUser(
            GetPokedexService::class,
            'GET',
            (string) file_get_contents('/var/www/html/tests/resources/unit/service/back/'.$filename),
            "album/{$trainerId}/{$dexSlug}",
            [
                'query' => $queryParams,
            ],
        );
    }
}
