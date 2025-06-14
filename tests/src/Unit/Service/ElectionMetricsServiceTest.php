<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Security\UserTokenService;
use App\Service\Api\ElectionMetricsApiService;
use App\Service\ElectionMetricsService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(ElectionMetricsService::class)]
class ElectionMetricsServiceTest extends TestCase
{
    public function testGetMetrics(): void
    {
        $userTokenService = $this->createMock(UserTokenService::class);
        $userTokenService
            ->expects($this->once())
            ->method('getLoggedUserToken')
            ->willReturn('8800088')
        ;

        $apiService = $this->createMock(ElectionMetricsApiService::class);
        $apiService
            ->expects($this->once())
            ->method('getMetrics')
            ->with(
                '8800088',
                'demo',
                'whatever',
            )
            ->willReturn([
                'view_count_sum' => 12,
                'win_count_sum' => 5,
                'view_count_max' => 4,
                'win_count_max' => 14,
                'under_max_view_count' => 24,
                'max_view_count' => 5,
                'dex_total_count' => 48,
            ])
        ;

        $service = new ElectionMetricsService($userTokenService, $apiService, 12);

        $metrics = $service->getMetrics('demo', 'whatever');

        $this->assertSame(12, $metrics->viewCountSum);
        $this->assertSame(5, $metrics->winCountSum);
        $this->assertSame(4, $metrics->viewCountMax);
        $this->assertSame(14, $metrics->winCountMax);
        $this->assertSame(24, $metrics->underMaxViewCount);
        $this->assertSame(5, $metrics->maxViewCount);
        $this->assertSame(1, $metrics->roundCount);
        $this->assertSame(5.0, $metrics->winnerAverage);
        $this->assertSame(8, $metrics->totalRoundCount);
    }
}
