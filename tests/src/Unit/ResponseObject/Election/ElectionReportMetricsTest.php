<?php

declare(strict_types=1);

namespace App\Tests\Unit\ResponseObject\Election;

use App\ResponseObject\Election\ElectionReportCompletion;
use App\ResponseObject\Election\ElectionReportMetrics;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(ElectionReportMetrics::class)]
final class ElectionReportMetricsTest extends TestCase
{
    public function testGetters(): void
    {
        $completion = new ElectionReportCompletion(atMaxCount: 5, underMaxCount: 1);
        $metrics = new ElectionReportMetrics(completion: $completion, dexTotalCount: 48, roundCount: 7, totalRoundCount: 8);

        $this->assertSame($completion, $metrics->getCompletion());
        $this->assertSame(48, $metrics->getDexTotalCount());
        $this->assertSame(7, $metrics->getRoundCount());
        $this->assertSame(8, $metrics->getTotalRoundCount());
    }
}
