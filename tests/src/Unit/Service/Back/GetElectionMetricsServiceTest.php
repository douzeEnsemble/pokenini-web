<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Back;

use App\Service\Back\GetElectionMetricsService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(GetElectionMetricsService::class)]
class GetElectionMetricsServiceTest extends TestCase
{
    use BackServiceTrait;

    public const ENDPOINT = 'election/metrics';

    public function testGet(): void
    {
        $items = $this
            ->getService(
                'home',
                'fav',
            )
            ->getMetrics(
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
    }

    public function testGetBis(): void
    {
        $items = $this
            ->getService(
                'demo',
                'pref',
            )
            ->getMetrics(
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
    }

    public function testGetWithFilters(): void
    {
        $items = $this
            ->getService(
                'home',
                'fav',
            )
            ->getMetrics(
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
    }

    public function testGetWithFiltersBis(): void
    {
        $items = $this
            ->getService(
                'demo',
                'pref',
            )
            ->getMetrics(
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
    }

    public function testWithoutLoggedUser(): void
    {
        $filename = '/app/tests/resources/unit/service/back/election_metrics_home_fav.json';

        /** @var GetElectionMetricsService $service */
        $service = $this->getServiceWithoutLoggedUser(
            GetElectionMetricsService::class,
            'GET',
            (string) file_get_contents(
                $filename,
            ),
            self::ENDPOINT,
            [
                'query' => [
                    'dex_slug' => 'home',
                    'election_slug' => 'fav',
                ],
            ],
        );

        $items = $service->getMetrics('home', 'fav');

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
    }

    private function getService(
        string $dexSlug,
        string $electionSlug,
    ): GetElectionMetricsService {
        $dir = '/app/tests/resources/unit/service/back';
        $filename = "{$dir}/election_metrics_{$dexSlug}_{$electionSlug}.json";

        /** @var GetElectionMetricsService */
        return $this->getServiceWithLoggedUser(
            GetElectionMetricsService::class,
            'GET',
            (string) file_get_contents($filename),
            self::ENDPOINT,
            [
                'query' => [
                    'dex_slug' => $dexSlug,
                    'election_slug' => $electionSlug,
                ],
            ],
        );
    }
}
