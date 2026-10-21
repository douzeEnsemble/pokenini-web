<?php

declare(strict_types=1);

namespace App\Tests\Unit\ResponseObject\ColorGame;

use App\ResponseObject\ColorGame\ColorGame;
use App\ResponseObject\ColorGame\ColorGameDexSummary;
use App\ResponseObject\ColorGame\ColorGamePokemon;
use App\ResponseObject\ColorGame\ColorGameRound;
use App\ResponseObject\ColorGame\ColorGameState;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(ColorGameState::class)]
final class ColorGameStateTest extends TestCase
{
    #[Test]
    public function getters(): void
    {
        $game = new ColorGame('g1', new ColorGameDexSummary('demo', 'Demo', 'Démo'), false, 'in_progress', 3, 1, 1, '2026-10-20T10:00:00+00:00', null, null);
        $round = new ColorGameRound(1, new ColorGamePokemon('ivysaur', 'Ivysaur', 'Herbizarre', 'Ivysaur', 'Herbizarre', '', '', 2, 'ivysaur'), []);
        $object = new ColorGameState($game, $round);

        $this->assertSame($game, $object->getGame());
        $this->assertSame($round, $object->getRound());
    }
}
