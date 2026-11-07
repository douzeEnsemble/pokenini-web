<?php

declare(strict_types=1);

namespace App\Tests\Unit\ResponseObject\Election;

use App\ResponseObject\Election\ElectionReport;
use App\ResponseObject\Election\ElectionReportCompletion;
use App\ResponseObject\Election\ElectionReportMetrics;
use App\ResponseObject\Election\TopPokemon;
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
        $this->assertSame([], $report->getTop());
        $this->assertNull($report->getFavourite());
    }

    #[Test]
    public function favourite(): void
    {
        $metrics = new ElectionReportMetrics(
            completion: new ElectionReportCompletion(atMaxCount: 5, underMaxCount: 1),
            dexTotalCount: 48,
            roundCount: 7,
            totalRoundCount: 8,
        );
        $first = (new \ReflectionClass(TopPokemon::class))->newInstanceWithoutConstructor();
        $second = (new \ReflectionClass(TopPokemon::class))->newInstanceWithoutConstructor();

        $report = new ElectionReport(metrics: $metrics, top: [$first, $second]);

        $this->assertSame([$first, $second], $report->getTop());
        $this->assertSame($first, $report->getFavourite());
    }
}
