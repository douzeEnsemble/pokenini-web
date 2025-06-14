<?php

declare(strict_types=1);

namespace App\Tests\Unit\Helper;

use App\Helper\TotalRoundCountHelper;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(TotalRoundCountHelper::class)]
class TotalRoundCountHelperTest extends TestCase
{
    #[DataProvider('providerCalculate')]
    public function testCalculate(
        int $dexTotalCount,
        int $perViewCount,
        float $winnerAverage,
        int $expectedCount,
    ): void {
        $this->assertSame(
            $expectedCount,
            TotalRoundCountHelper::calculate(
                $dexTotalCount,
                $perViewCount,
                $winnerAverage,
            ),
        );
    }

    /**
     * @return float[][]|int[][]
     */
    public static function providerCalculate(): array
    {
        return [
            'dexTotalCount_0' => [
                'dexTotalCount' => 0,
                'perViewCount' => 12,
                'winnerAverage' => 0.0,
                'expectedCount' => 0,
            ],
            'winnerAverage_0' => [
                'dexTotalCount' => 10,
                'perViewCount' => 12,
                'winnerAverage' => 0.0,
                'expectedCount' => 0,
            ],
            'orelsan_gmax' => [
                'dexTotalCount' => 35,
                'perViewCount' => 12,
                'winnerAverage' => 3.5,
                'expectedCount' => 5,
            ],
            'douze_starters' => [
                'dexTotalCount' => 85,
                'perViewCount' => 12,
                'winnerAverage' => 1.14,
                'expectedCount' => 10,
            ],
            'douze_mega' => [
                'dexTotalCount' => 50,
                'perViewCount' => 12,
                'winnerAverage' => 3.0,
                'expectedCount' => 8,
            ],
            'ok_metrics' => [
                'dexTotalCount' => 50,
                'perViewCount' => 12,
                'winnerAverage' => 7.71,
                'expectedCount' => 13,
            ],
            'service_metrics' => [
                'dexTotalCount' => 48,
                'perViewCount' => 12,
                'winnerAverage' => 5.0,
                'expectedCount' => 8,
            ],
            'edge_while' => [
                'dexTotalCount' => 0,
                'perViewCount' => 12,
                'winnerAverage' => 3.9,
                'expectedCount' => 0,
            ],
            'edge_rounding_floor' => [
                'dexTotalCount' => 50,
                'perViewCount' => 10,
                'winnerAverage' => 4.5,
                'expectedCount' => 10,
            ],
        ];
    }

    public function testCalculateZeroPerViewCount(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("perViewCount can't be egals to 0");
        TotalRoundCountHelper::calculate(
            12,
            0,
            1.0,
        );
    }
}
