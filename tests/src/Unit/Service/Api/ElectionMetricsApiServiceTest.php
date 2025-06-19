<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Api;

use App\Service\Api\ElectionMetricsApiService;
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
#[CoversClass(ElectionMetricsApiService::class)]
class ElectionMetricsApiServiceTest extends TestCase
{
    private ArrayAdapter $cachePool;
    private TagAwareAdapter $cache;

    public function testGet(): void
    {
        $items = $this
            ->getService(
                '4564650',
                'home',
                'fav',
            )
            ->getMetrics(
                '4564650',
                'home',
                'fav',
            )
        ;

        $this->assertSame(
            [
                'view_count_sum' => 6,
                'win_count_sum' => 2,
                'view_count_max' => 1,
                'win_count_max' => 1,
                'under_max_view_count' => 1,
                'max_view_count' => 5,
                'dex_total_count' => 48,
            ],
            $items
        );

        $this->assertEmpty($this->cachePool->getValues());
    }

    public function testGetBis(): void
    {
        $items = $this
            ->getService(
                '87654',
                'demo',
                'pref',
            )
            ->getMetrics(
                '87654',
                'demo',
                'pref',
            )
        ;

        $this->assertSame(
            [
                'view_count_sum' => 5,
                'win_count_sum' => 10,
                'view_count_max' => 1,
                'win_count_max' => 1,
                'under_max_view_count' => 1,
                'max_view_count' => 5,
                'dex_total_count' => 48,
            ],
            $items
        );

        $this->assertEmpty($this->cachePool->getValues());
    }

    public function testGetWithFilters(): void
    {
        $items = $this
            ->getService(
                '4564650',
                'home',
                'fav',
            )
            ->getMetrics(
                '4564650',
                'home',
                'fav',
            )
        ;

        $this->assertSame(
            [
                'view_count_sum' => 6,
                'win_count_sum' => 2,
                'view_count_max' => 1,
                'win_count_max' => 1,
                'under_max_view_count' => 1,
                'max_view_count' => 5,
                'dex_total_count' => 48,
            ],
            $items
        );

        $this->assertEmpty($this->cachePool->getValues());
    }

    public function testGetWithFiltersBis(): void
    {
        $items = $this
            ->getService(
                '87654',
                'demo',
                'pref',
            )
            ->getMetrics(
                '87654',
                'demo',
                'pref',
            )
        ;

        $this->assertSame(
            [
                'view_count_sum' => 5,
                'win_count_sum' => 10,
                'view_count_max' => 1,
                'win_count_max' => 1,
                'under_max_view_count' => 1,
                'max_view_count' => 5,
                'dex_total_count' => 48,
            ],
            $items
        );

        $this->assertEmpty($this->cachePool->getValues());
    }

    private function getService(
        string $trainerId,
        string $dexSlug,
        string $electionSlug,
    ): ElectionMetricsApiService {
        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects($this->exactly(2))
            ->method('info')
        ;

        $client = $this->createMock(HttpClientInterface::class);

        $dir = '/var/www/html/tests/resources/unit/service/api';
        $json = (string) file_get_contents(
            "{$dir}/election_metrics_{$trainerId}_{$dexSlug}_{$electionSlug}.json"
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
                'https://api.domain/election/metrics',
                [
                    'query' => [
                        'trainer_external_id' => $trainerId,
                        'dex_slug' => $dexSlug,
                        'election_slug' => $electionSlug,
                    ],
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

        return new ElectionMetricsApiService(
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
