<?php

declare(strict_types=1);

namespace App\Tests\Unit\ResponseObject\ShinyGame;

use App\ResponseObject\ShinyGame\ShinyGame;
use App\ResponseObject\ShinyGame\ShinyGameAnswer;
use App\ResponseObject\ShinyGame\ShinyGameDexSummary;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(ShinyGameAnswer::class)]
final class ShinyGameAnswerTest extends TestCase
{
    #[Test]
    public function getters(): void
    {
        $game = new ShinyGame('g1', new ShinyGameDexSummary('demo', 'Demo', 'Démo'), 'in_progress', 3, 1, 1, '2026-10-20T10:00:00+00:00', null, null);
        $object = new ShinyGameAnswer(2, false, true, false, $game);

        $this->assertSame(2, $object->getPosition());
        $this->assertSame(false, $object->isCorrect());
        $this->assertSame(true, $object->isChosenShiny());
        $this->assertSame(false, $object->isShiny());
        $this->assertSame($game, $object->getGame());
    }
}
