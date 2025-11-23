<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Back;

use App\Service\Back\GetPokemonsService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(GetPokemonsService::class)]
class GetPokemonsServiceTest extends TestCase
{
    use BackServiceTrait;

    #[DataProvider('providerGet')]
    public function testGet(
        string $dexSlug,
        string $electionSlug,
        int $count,
    ): void {
        $electionList = $this
            ->getService(
                $dexSlug,
                $electionSlug,
                $count,
            )
            ->get(
                $dexSlug,
                $electionSlug,
                $count,
                [],
            )
        ;

        $pokemons = $electionList->items;
        $this->assertCount($count, $pokemons);
    }

    /**
     * @return int[][]|string[][]
     */
    public static function providerGet(): array
    {
        return [
            '123-3' => [
                'dexSlug' => '123',
                'electionSlug' => '',
                'count' => 3,
            ],
            '123-5' => [
                'dexSlug' => '123',
                'electionSlug' => 'a',
                'count' => 5,
            ],
            'all-12' => [
                'dexSlug' => 'all',
                'electionSlug' => 'b',
                'count' => 12,
            ],
        ];
    }

    public function testGetWithFilters(): void
    {
        $electionList = $this
            ->getService(
                '123',
                '',
                5,
                '_cflegendary',
                [
                    'cf' => ['legendary'],
                ],
            )
            ->get(
                '123',
                '',
                5,
                [
                    'cf' => ['legendary'],
                ],
            )
        ;

        $this->assertSame('pick', $electionList->type);

        $pokemons = $electionList->items;
        $this->assertCount(5, $pokemons);
    }

    public function testGetWithoutLoggedUser(): void
    {
        $dir = '/var/www/html/tests/resources/unit/service/back';
        $filename = "{$dir}/pokemons_123__3.json";

        /** @var GetPokemonsService $service */
        $service = $this->getServiceWithoutLoggedUser(
            GetPokemonsService::class,
            'GET',
            (string) file_get_contents($filename),
            'pokemons/to_choose',
            [
                'query' => [
                    'dex_slug' => '123',
                    'election_slug' => '',
                    'count' => 3,
                ],
            ]
        );

        $electionList = $service->get('123', '', 3, []);

        $this->assertSame('pick', $electionList->type);

        $pokemons = $electionList->items;
        $this->assertCount(3, $pokemons);
    }

    /**
     * @param array<string, array<int, string>|string> $filters
     */
    private function getService(
        string $dexSlug,
        string $electionSlug,
        int $count,
        string $filtersStr = '',
        array $filters = [],
    ): GetPokemonsService {
        $dir = '/var/www/html/tests/resources/unit/service/back';
        $filename = "{$dir}/pokemons_{$dexSlug}_{$electionSlug}_{$count}{$filtersStr}.json";

        $options = [
            'query' => array_merge(
                [
                    'dex_slug' => $dexSlug,
                    'election_slug' => $electionSlug,
                    'count' => "{$count}",
                ],
                $filters,
            ),
        ];

        /** @var GetPokemonsService */
        return $this->getServiceWithLoggedUser(
            GetPokemonsService::class,
            'GET',
            (string) file_get_contents($filename),
            'pokemons/to_choose',
            $options,
        );
    }
}
