<?php

declare(strict_types=1);

namespace App\Tests\Unit\ResponseObject\ColorGame;

use App\ResponseObject\ColorGame\ColorGame;
use App\ResponseObject\ColorGame\ColorGameAnsweredRound;
use App\ResponseObject\ColorGame\ColorGameDexSummary;
use App\ResponseObject\ColorGame\ColorGamePokemon;
use App\ResponseObject\ColorGame\ColorGameRounds;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(ColorGameRounds::class)]
final class ColorGameRoundsTest extends TestCase
{
    #[Test]
    public function getters(): void
    {
        $game = new ColorGame('g1', new ColorGameDexSummary('demo', 'Demo', 'Démo'), false, 'completed', 3, 3, 1, '2026-10-20T10:00:00+00:00', null, null);
        $rounds = [new ColorGameAnsweredRound(4, new ColorGamePokemon('ivysaur', 'Ivysaur', 'Herbizarre', 'Ivysaur', 'Herbizarre', '', '', 2, 'ivysaur'), true, [], 1, 1)];
        $object = new ColorGameRounds($game, $rounds);

        $this->assertSame($game, $object->getGame());
        $this->assertSame($rounds, $object->getRounds());
    }
}
