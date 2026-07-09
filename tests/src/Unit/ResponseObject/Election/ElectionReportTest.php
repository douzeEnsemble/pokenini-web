<?php

declare(strict_types=1);

namespace App\Tests\Unit\ResponseObject\Election;

use App\ResponseObject\Election\ElectionReport;
use App\ResponseObject\Election\ElectionReportCompletion;
use App\ResponseObject\Election\ElectionReportMetrics;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(ElectionReport::class)]
final class ElectionReportTest extends TestCase
{
    public function testGetters(): void
    {
        $metrics = new ElectionReportMetrics(
            completion: new ElectionReportCompletion(atMaxCount: 5, underMaxCount: 1),
            dexTotalCount: 48,
        );
        $report = new ElectionReport(metrics: $metrics);

        $this->assertSame($metrics, $report->getMetrics());
    }
}
