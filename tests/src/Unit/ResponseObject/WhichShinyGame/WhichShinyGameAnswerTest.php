<?php

declare(strict_types=1);

namespace App\Tests\Unit\ResponseObject\WhichShinyGame;

use App\ResponseObject\WhichShinyGame\WhichShinyGame;
use App\ResponseObject\WhichShinyGame\WhichShinyGameAnswer;
use App\ResponseObject\WhichShinyGame\WhichShinyGameDexSummary;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(WhichShinyGameAnswer::class)]
final class WhichShinyGameAnswerTest extends TestCase
{
    #[Test]
    public function getters(): void
    {
        $game = new WhichShinyGame('g1', new WhichShinyGameDexSummary('demo', 'Demo', 'Démo'), 'in_progress', 3, 1, 1, '2026-10-20T10:00:00+00:00', null, null);
        $object = new WhichShinyGameAnswer(2, false, 2, 1, $game);

        $this->assertSame(2, $object->getPosition());
        $this->assertSame(false, $object->isCorrect());
        $this->assertSame(2, $object->getChosenSlot());
        $this->assertSame(1, $object->getShinySlot());
        $this->assertSame($game, $object->getGame());
    }
}
