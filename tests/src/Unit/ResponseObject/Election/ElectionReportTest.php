<?php

declare(strict_types=1);

namespace App\Tests\Unit\ResponseObject\Election;

use App\ResponseObject\Election\ElectionReport;
use App\ResponseObject\Election\ElectionReportCompletion;
use App\ResponseObject\Election\ElectionReportMetrics;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(ElectionReport::class)]
final class ElectionReportTest extends TestCase
{
    #[Test]
    public function getters(): void
    {
        $metrics = new ElectionReportMetrics(
            completion: new ElectionReportCompletion(atMaxCount: 5, underMaxCount: 1),
            dexTotalCount: 48,
            roundCount: 7,
            totalRoundCount: 8,
        );
        $report = new ElectionReport(metrics: $metrics);

        $this->assertSame($metrics, $report->getMetrics());
    }
}
