<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Api;

use App\Service\Api\GetPokemonsService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Adapter\TagAwareAdapter;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * @internal
 */
#[CoversClass(GetPokemonsService::class)]
class GetPokemonsServiceTest extends TestCase
{
    private ArrayAdapter $cachePool;
    private TagAwareAdapter $cache;

    #[DataProvider('providerGet')]
    public function testGet(
        string $listType,
        string $trainerExternalId,
        string $dexSlug,
        string $electionSlug,
        int $count,
    ): void {
        $electionList = $this
            ->getService(
                $listType,
                $trainerExternalId,
                $dexSlug,
                $electionSlug,
                $count,
            )
            ->get(
                $trainerExternalId,
                $dexSlug,
                $electionSlug,
                $count,
                [],
            )
        ;

        $this->assertSame($listType, $electionList->type);

        $pokemons = $electionList->items;
        $this->assertCount($count, $pokemons);

        $this->assertEmpty($this->cache->getItems());
    }

    public function testGetWithFilters(): void
    {
        $electionList = $this
            ->getService(
                'pick',
                '12',
                '123',
                '',
                5,
                '_cflegendary',
                [
                    'cf' => ['legendary'],
                ],
            )
            ->get(
                '12',
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

        $this->assertEmpty($this->cache->getItems());
    }

    /**
     * @return int[][]|string[][]
     */
    public static function providerGet(): array
    {
        return [
            '123-3' => [
                'listType' => 'pick',
                'trainerExternalId' => '12',
                'dexSlug' => '123',
                'electionSlug' => '',
                'count' => 3,
            ],
            '123-5' => [
                'listType' => 'pick',
                'trainerExternalId' => '13',
                'dexSlug' => '123',
                'electionSlug' => 'a',
                'count' => 5,
            ],
            'all-12' => [
                'listType' => 'vote',
                'trainerExternalId' => '14',
                'dexSlug' => 'all',
                'electionSlug' => 'b',
                'count' => 12,
            ],
        ];
    }

    /**
     * @param string[][] $filters
     */
    private function getService(
        string $listType,
        string $trainerExternalId,
        string $dexSlug,
        string $electionSlug,
        int $count,
        string $filtersStr = '',
        array $filters = [],
    ): GetPokemonsService {
        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects($this->exactly(2))
            ->method('info')
        ;

        $client = $this->createMock(HttpClientInterface::class);

        $dir = '/var/www/html/tests/resources/unit/service/api';
        $json = (string) file_get_contents(
            "{$dir}/pokemons_to{$listType}_{$trainerExternalId}_{$dexSlug}_{$electionSlug}_{$count}{$filtersStr}.json"
        );

        $response = $this->createMock(ResponseInterface::class);
        $response
            ->expects($this->exactly(2))
            ->method('getContent')
            ->willReturn($json)
        ;

        $client
            ->expects($this->once())
            ->method('request')
            ->with(
                'GET',
                'https://api.domain/pokemons/to_choose',
                [
                    'query' => array_merge(
                        [
                            'trainer_external_id' => $trainerExternalId,
                            'dex_slug' => $dexSlug,
                            'election_slug' => $electionSlug,
                            'count' => $count,
                        ],
                        $filters,
                    ),
                    'headers' => [
                        'accept' => 'application/json',
                    ],
                    'auth_basic' => [
                        'web',
                        'douze',
                    ],
                    'cafile' => './resources/certificates/cacert.pem',
                ],
            )
            ->willReturn($response)
        ;

        $this->cachePool = new ArrayAdapter();
        $this->cache = new TagAwareAdapter($this->cachePool, new ArrayAdapter());

        return new GetPokemonsService(
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
