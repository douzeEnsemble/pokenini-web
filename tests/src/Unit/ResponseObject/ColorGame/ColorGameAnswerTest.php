<?php

declare(strict_types=1);

namespace App\Tests\Unit\ResponseObject\ColorGame;

use App\ResponseObject\ColorGame\ColorGame;
use App\ResponseObject\ColorGame\ColorGameAnswer;
use App\ResponseObject\ColorGame\ColorGameDexSummary;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(ColorGameAnswer::class)]
final class ColorGameAnswerTest extends TestCase
{
    #[Test]
    public function getters(): void
    {
        $game = new ColorGame('g1', new ColorGameDexSummary('demo', 'Demo', 'Démo'), false, 'in_progress', 3, 1, 1, '2026-10-20T10:00:00+00:00', null, null);
        $object = new ColorGameAnswer(2, true, 1, 3, $game);

        $this->assertSame(2, $object->getPosition());
        $this->assertSame(true, $object->isCorrect());
        $this->assertSame(1, $object->getChosenChoice());
        $this->assertSame(3, $object->getCorrectChoice());
        $this->assertSame($game, $object->getGame());
    }
}
