<?php

declare(strict_types=1);

namespace App\Tests\Unit\DTO;

use App\DTO\ElectionIndexData;
use App\DTO\ElectionMetrics;
use App\DTO\ElectionTop;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(ElectionIndexData::class)]
final class ElectionIndexDataTest extends TestCase
{
    public function testOk(): void
    {
        $object = new ElectionIndexData(
            'list_type',
            [],
            null,
            new ElectionTop([]),
            ElectionMetrics::createFromArray(
                [
                    'view_count' => ['sum' => 82, 'max' => 42],
                    'win_count' => ['sum' => 54, 'max' => 52],
                    'completion' => ['under_max_count' => 62, 'at_max_count' => 27],
                    'dex_total_count' => 50,
                    'round_count' => 7,
                    'winner_average' => 7.71,
                    'total_round_count' => 13,
                ]
            ),
            0,
            true,
            true,
        );

        $this->assertSame('list_type', $object->listType);
        $this->assertSame([], $object->pokemons);
        $this->assertSame(null, $object->pokedex);
        $this->assertCount(0, $object->electionTop->getItems());
        $this->assertSame(82, $object->metrics->viewCountSum);
        $this->assertSame(54, $object->metrics->winCountSum);
        $this->assertSame(42, $object->metrics->viewCountMax);
        $this->assertSame(52, $object->metrics->winCountMax);
        $this->assertSame(62, $object->metrics->underMaxViewCount);
        $this->assertSame(27, $object->metrics->maxViewCount);
        $this->assertSame(50, $object->metrics->dexTotalCount);
        $this->assertSame(7, $object->metrics->roundCount);
        $this->assertSame(7.71, $object->metrics->winnerAverage);
        $this->assertSame(13, $object->metrics->totalRoundCount);

        $this->assertSame(0, $object->detachedCount);
        $this->assertSame(true, $object->isTheLastOne);
        $this->assertSame(true, $object->isTheLastPage);
    }
}
