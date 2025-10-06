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
        $service = $this->getService(
            'lite',
            'pokedex_lite_csyes.json',
            [
                'catch_states' => [
                    'yes',
                ],
            ],
        );

        $pokedex = $service->get(
            'lite',
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
    }

    public function testGetWithTrainerId(): void
    {
        $service = $this->getService(
            'lite',
            'pokedex_lite.json',
            [
                'trainer_id' => '123',
                'catch_states' => [
                    'yes',
                ],
            ],
        );

        $pokedex = $service->getWithTrainerId(
            '123',
            'lite',
            [
                'catch_states' => [
                    'yes',
                ],
            ],
        );

        $this->assertArrayHasKey('dex', $pokedex);
        $this->assertArrayHasKey('pokemons', $pokedex);
        $this->assertCount(41, $pokedex['pokemons']);
        $this->assertArrayHasKey('report', $pokedex);
    }

    /**
     * @param string[]|string[][]|string[][][] $queryParams
     */
    private function getService(
        string $dexSlug,
        string $filename,
        array $queryParams,
    ): GetPokedexService {
        /** @var GetPokedexService */
        return $this->getServiceWithLoggedUser(
            GetPokedexService::class,
            'GET',
            (string) file_get_contents('/var/www/html/tests/resources/unit/service/back/'.$filename),
            "album/{$dexSlug}",
            [
                'query' => $queryParams,
            ],
        );
    }
}
