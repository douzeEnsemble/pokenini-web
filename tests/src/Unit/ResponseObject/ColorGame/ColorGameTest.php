<?php

declare(strict_types=1);

namespace App\Tests\Unit\ResponseObject\ColorGame;

use App\ResponseObject\ColorGame\ColorGame;
use App\ResponseObject\ColorGame\ColorGameDexSummary;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(ColorGame::class)]
final class ColorGameTest extends TestCase
{
    #[Test]
    public function getters(): void
    {
        $dex = new ColorGameDexSummary('demo', 'Demo', 'Démo');
        $object = new ColorGame('g1', $dex, true, 'completed', 3, 2, 1, '2026-10-20T10:00:00+00:00', '2026-10-20T10:01:00+00:00', '2026-10-20T10:02:00+00:00', [true, false]);

        $this->assertSame('g1', $object->getId());
        $this->assertSame($dex, $object->getDex());
        $this->assertSame(true, $object->isShiny());
        $this->assertSame('completed', $object->getStatus());
        $this->assertSame(3, $object->getRoundCount());
        $this->assertSame(2, $object->getAnsweredCount());
        $this->assertSame(1, $object->getCorrectCount());
        $this->assertSame('2026-10-20T10:00:00+00:00', $object->getStartedAt());
        $this->assertSame('2026-10-20T10:01:00+00:00', $object->getLastPlayedAt());
        $this->assertSame('2026-10-20T10:02:00+00:00', $object->getEndedAt());
        $this->assertSame([true, false], $object->getResults());
    }

    #[Test]
    public function resultsDefaultToNone(): void
    {
        $dex = new ColorGameDexSummary('demo', 'Demo', 'Démo');
        $object = new ColorGame('g1', $dex, true, 'completed', 3, 2, 1, '2026-10-20T10:00:00+00:00', '2026-10-20T10:01:00+00:00', '2026-10-20T10:02:00+00:00');

        $this->assertSame([], $object->getResults());
    }
}
