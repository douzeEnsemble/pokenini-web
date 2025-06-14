<?php

declare(strict_types=1);

namespace App\Tests\Unit\DTO;

use App\DTO\ElectionMetrics;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;

/**
 * @internal
 */
#[CoversClass(ElectionMetrics::class)]
class ElectionMetricsTest extends TestCase
{
    public function testOk(): void
    {
        $object = new ElectionMetrics(
            [
                'view_count_sum' => 82,
                'win_count_sum' => 54,
                'view_count_max' => 42,
                'win_count_max' => 52,
                'under_max_view_count' => 62,
                'max_view_count' => 27,
                'dex_total_count' => 50,
            ],
            12,
        );

        $this->assertSame(82, $object->viewCountSum);
        $this->assertSame(54, $object->winCountSum);
        $this->assertSame(42, $object->viewCountMax);
        $this->assertSame(52, $object->winCountMax);
        $this->assertSame(62, $object->underMaxViewCount);
        $this->assertSame(27, $object->maxViewCount);
        $this->assertSame(50, $object->dexTotalCount);

        $this->assertSame(7, $object->roundCount);
        $this->assertSame(7.71, $object->winnerAverage);
        $this->assertSame(13, $object->totalRoundCount);
    }

    public function testZeros(): void
    {
        $object = new ElectionMetrics(
            [
                'view_count_sum' => 0,
                'win_count_sum' => 0,
                'view_count_max' => 0,
                'win_count_max' => 0,
                'under_max_view_count' => 0,
                'max_view_count' => 0,
                'dex_total_count' => 50,
            ],
            12,
        );

        $this->assertSame(0, $object->viewCountSum);
        $this->assertSame(0, $object->winCountSum);
        $this->assertSame(0, $object->viewCountMax);
        $this->assertSame(0, $object->winCountMax);
        $this->assertSame(0, $object->underMaxViewCount);
        $this->assertSame(0, $object->maxViewCount);
        $this->assertSame(50, $object->dexTotalCount);

        $this->assertSame(0, $object->roundCount);
        $this->assertSame(4.0, $object->winnerAverage);
        $this->assertSame(9, $object->totalRoundCount);
    }

    public function testEdge(): void
    {
        $object = new ElectionMetrics(
            [
                'view_count_sum' => 1,
                'win_count_sum' => 1,
                'view_count_max' => 1,
                'win_count_max' => 1,
                'under_max_view_count' => 1,
                'max_view_count' => 1,
                'dex_total_count' => 50,
            ],
            120,
        );

        $this->assertSame(1, $object->viewCountSum);
        $this->assertSame(1, $object->winCountSum);
        $this->assertSame(1, $object->viewCountMax);
        $this->assertSame(1, $object->winCountMax);
        $this->assertSame(1, $object->underMaxViewCount);
        $this->assertSame(1, $object->maxViewCount);
        $this->assertSame(50, $object->dexTotalCount);

        $this->assertSame(0, $object->roundCount);
        $this->assertSame(4.0, $object->winnerAverage);
        $this->assertSame(2, $object->totalRoundCount);
    }

    public function testLowerAverage(): void
    {
        $object = new ElectionMetrics(
            [
                'view_count_sum' => 48,
                'win_count_sum' => 3,
                'view_count_max' => 2,
                'win_count_max' => 2,
                'under_max_view_count' => 0,
                'max_view_count' => 3,
                'dex_total_count' => 48,
            ],
            12,
        );

        $this->assertSame(48, $object->viewCountSum);
        $this->assertSame(3, $object->winCountSum);
        $this->assertSame(2, $object->viewCountMax);
        $this->assertSame(2, $object->winCountMax);
        $this->assertSame(0, $object->underMaxViewCount);
        $this->assertSame(3, $object->maxViewCount);
        $this->assertSame(48, $object->dexTotalCount);

        $this->assertSame(4, $object->roundCount);
        $this->assertSame(0.75, $object->winnerAverage);
        $this->assertSame(6, $object->totalRoundCount);
    }

    public function testFloor(): void
    {
        $object = new ElectionMetrics(
            [
                'view_count_sum' => 120,
                'win_count_sum' => 28,
                'view_count_max' => 1,
                'win_count_max' => 1,
                'under_max_view_count' => 1,
                'max_view_count' => 1,
                'dex_total_count' => 17,
            ],
            12,
        );

        $this->assertSame(120, $object->viewCountSum);
        $this->assertSame(28, $object->winCountSum);
        $this->assertSame(1, $object->viewCountMax);
        $this->assertSame(1, $object->winCountMax);
        $this->assertSame(1, $object->underMaxViewCount);
        $this->assertSame(1, $object->maxViewCount);
        $this->assertSame(17, $object->dexTotalCount);

        $this->assertSame(10, $object->roundCount);
        $this->assertSame(2.8, $object->winnerAverage);
        $this->assertSame(4, $object->totalRoundCount);
    }

    public function testMissingViewCountSum(): void
    {
        $object = new ElectionMetrics(
            [
                'win_count_sum' => 2,
                'view_count_max' => 3,
                'win_count_max' => 4,
                'under_max_view_count' => 5,
                'max_view_count' => 6,
                'dex_total_count' => 50,
            ],
            12,
        );

        $this->assertSame(0, $object->viewCountSum);
        $this->assertSame(2, $object->winCountSum);
        $this->assertSame(3, $object->viewCountMax);
        $this->assertSame(4, $object->winCountMax);
        $this->assertSame(5, $object->underMaxViewCount);
        $this->assertSame(6, $object->maxViewCount);
        $this->assertSame(50, $object->dexTotalCount);

        $this->assertSame(0, $object->roundCount);
        $this->assertSame(4.0, $object->winnerAverage);
        $this->assertSame(9, $object->totalRoundCount);
    }

    public function testBadViewCountSum(): void
    {
        $this->expectException(InvalidOptionsException::class);
        new ElectionMetrics(
            [
                'view_count_sum' => '1',
                'win_count_sum' => 2,
                'view_count_max' => 3,
                'win_count_max' => 4,
                'under_max_view_count' => 5,
                'max_view_count' => 6,
                'dex_total_count' => 50,
            ],
            12,
        );
    }

    public function testMissingWinCountSum(): void
    {
        $object = new ElectionMetrics(
            [
                'view_count_sum' => 1,
                'view_count_max' => 3,
                'win_count_max' => 4,
                'under_max_view_count' => 5,
                'max_view_count' => 6,
                'dex_total_count' => 50,
            ],
            12,
        );

        $this->assertSame(1, $object->viewCountSum);
        $this->assertSame(0, $object->winCountSum);
        $this->assertSame(3, $object->viewCountMax);
        $this->assertSame(4, $object->winCountMax);
        $this->assertSame(5, $object->underMaxViewCount);
        $this->assertSame(6, $object->maxViewCount);
        $this->assertSame(50, $object->dexTotalCount);

        $this->assertSame(0, $object->roundCount);
        $this->assertSame(4.0, $object->winnerAverage);
        $this->assertSame(9, $object->totalRoundCount);
    }

    public function testBadWinCountSum(): void
    {
        $this->expectException(InvalidOptionsException::class);
        new ElectionMetrics(
            [
                'view_count_sum' => 1,
                'win_count_sum' => '2',
                'view_count_max' => 3,
                'win_count_max' => 4,
                'under_max_view_count' => 5,
                'max_view_count' => 6,
                'dex_total_count' => 50,
            ],
            12,
        );
    }

    public function testMissingViewCountMax(): void
    {
        $object = new ElectionMetrics(
            [
                'view_count_sum' => 1,
                'win_count_sum' => 2,
                'win_count_max' => 4,
                'under_max_view_count' => 5,
                'max_view_count' => 6,
                'dex_total_count' => 50,
            ],
            12,
        );

        $this->assertSame(1, $object->viewCountSum);
        $this->assertSame(2, $object->winCountSum);
        $this->assertSame(0, $object->viewCountMax);
        $this->assertSame(4, $object->winCountMax);
        $this->assertSame(5, $object->underMaxViewCount);
        $this->assertSame(6, $object->maxViewCount);
        $this->assertSame(50, $object->dexTotalCount);

        $this->assertSame(0, $object->roundCount);
        $this->assertSame(4.0, $object->winnerAverage);
        $this->assertSame(9, $object->totalRoundCount);
    }

    public function testBadViewCountMax(): void
    {
        $this->expectException(InvalidOptionsException::class);
        new ElectionMetrics(
            [
                'view_count_sum' => 1,
                'win_count_sum' => 2,
                'view_count_max' => '3',
                'win_count_max' => 4,
                'under_max_view_count' => 5,
                'max_view_count' => 6,
                'dex_total_count' => 50,
            ],
            12,
        );
    }

    public function testMissingWinCountMax(): void
    {
        $object = new ElectionMetrics(
            [
                'view_count_sum' => 1,
                'win_count_sum' => 2,
                'view_count_max' => 3,
                'under_max_view_count' => 5,
                'max_view_count' => 6,
                'dex_total_count' => 50,
            ],
            12,
        );

        $this->assertSame(1, $object->viewCountSum);
        $this->assertSame(2, $object->winCountSum);
        $this->assertSame(3, $object->viewCountMax);
        $this->assertSame(0, $object->winCountMax);
        $this->assertSame(5, $object->underMaxViewCount);
        $this->assertSame(6, $object->maxViewCount);
        $this->assertSame(50, $object->dexTotalCount);

        $this->assertSame(0, $object->roundCount);
        $this->assertSame(4.0, $object->winnerAverage);
        $this->assertSame(9, $object->totalRoundCount);
    }

    public function testBadWinCountMax(): void
    {
        $this->expectException(InvalidOptionsException::class);
        new ElectionMetrics(
            [
                'view_count_sum' => 1,
                'win_count_sum' => 2,
                'view_count_max' => 3,
                'win_count_max' => '4',
                'under_max_view_count' => 5,
                'max_view_count' => 6,
                'dex_total_count' => 50,
            ],
            12,
        );
    }

    public function testMissingUnderMaxViewCount(): void
    {
        $object = new ElectionMetrics(
            [
                'view_count_sum' => 1,
                'win_count_sum' => 2,
                'view_count_max' => 3,
                'win_count_max' => 4,
                'max_view_count' => 6,
                'dex_total_count' => 50,
            ],
            12,
        );

        $this->assertSame(1, $object->viewCountSum);
        $this->assertSame(2, $object->winCountSum);
        $this->assertSame(3, $object->viewCountMax);
        $this->assertSame(4, $object->winCountMax);
        $this->assertSame(0, $object->underMaxViewCount);
        $this->assertSame(6, $object->maxViewCount);
        $this->assertSame(50, $object->dexTotalCount);

        $this->assertSame(0, $object->roundCount);
        $this->assertSame(4.0, $object->winnerAverage);
        $this->assertSame(9, $object->totalRoundCount);
    }

    public function testBadUnderMaxViewCount(): void
    {
        $this->expectException(InvalidOptionsException::class);
        new ElectionMetrics(
            [
                'view_count_sum' => 1,
                'win_count_sum' => 2,
                'view_count_max' => 3,
                'win_count_max' => 4,
                'under_max_view_count' => '5',
                'max_view_count' => 6,
                'dex_total_count' => 50,
            ],
            12,
        );
    }

    public function testMissingMaxViewCount(): void
    {
        $object = new ElectionMetrics(
            [
                'view_count_sum' => 1,
                'win_count_sum' => 2,
                'view_count_max' => 3,
                'win_count_max' => 4,
                'under_max_view_count' => 5,
                'dex_total_count' => 50,
            ],
            12,
        );

        $this->assertSame(1, $object->viewCountSum);
        $this->assertSame(2, $object->winCountSum);
        $this->assertSame(3, $object->viewCountMax);
        $this->assertSame(4, $object->winCountMax);
        $this->assertSame(5, $object->underMaxViewCount);
        $this->assertSame(0, $object->maxViewCount);
        $this->assertSame(50, $object->dexTotalCount);

        $this->assertSame(0, $object->roundCount);
        $this->assertSame(4.0, $object->winnerAverage);
        $this->assertSame(9, $object->totalRoundCount);
    }

    public function testBadMaxViewCount(): void
    {
        $this->expectException(InvalidOptionsException::class);
        new ElectionMetrics(
            [
                'view_count_sum' => 1,
                'win_count_sum' => 2,
                'view_count_max' => 3,
                'win_count_max' => 4,
                'under_max_view_count' => 5,
                'max_view_count' => '6',
                'dex_total_count' => 50,
            ],
            12,
        );
    }

    public function testMissingDexTotalCount(): void
    {
        $object = new ElectionMetrics(
            [
                'view_count_sum' => 1,
                'win_count_sum' => 2,
                'view_count_max' => 3,
                'win_count_max' => 4,
                'under_max_view_count' => 5,
                'max_view_count' => 6,
            ],
            12,
        );

        $this->assertSame(1, $object->viewCountSum);
        $this->assertSame(2, $object->winCountSum);
        $this->assertSame(3, $object->viewCountMax);
        $this->assertSame(4, $object->winCountMax);
        $this->assertSame(5, $object->underMaxViewCount);
        $this->assertSame(0, $object->dexTotalCount);

        $this->assertSame(0, $object->roundCount);
        $this->assertSame(4.0, $object->winnerAverage);
        $this->assertSame(0, $object->totalRoundCount);
    }

    public function testBadDexTotalCount(): void
    {
        $this->expectException(InvalidOptionsException::class);
        new ElectionMetrics(
            [
                'view_count_sum' => 1,
                'win_count_sum' => 2,
                'view_count_max' => 3,
                'win_count_max' => 4,
                'under_max_view_count' => 5,
                'max_view_count' => 6,
                'dex_total_count' => '50',
            ],
            12,
        );
    }
}
