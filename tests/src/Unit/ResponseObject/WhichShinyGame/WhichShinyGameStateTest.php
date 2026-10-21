<?php

declare(strict_types=1);

namespace App\Tests\Unit\ResponseObject\WhichShinyGame;

use App\ResponseObject\WhichShinyGame\WhichShinyGame;
use App\ResponseObject\WhichShinyGame\WhichShinyGameDexSummary;
use App\ResponseObject\WhichShinyGame\WhichShinyGamePokemon;
use App\ResponseObject\WhichShinyGame\WhichShinyGameRound;
use App\ResponseObject\WhichShinyGame\WhichShinyGameState;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(WhichShinyGameState::class)]
final class WhichShinyGameStateTest extends TestCase
{
    #[Test]
    public function getters(): void
    {
        $game = new WhichShinyGame('g1', new WhichShinyGameDexSummary('demo', 'Demo', 'Démo'), 'in_progress', 3, 1, 1, '2026-10-20T10:00:00+00:00', null, null);
        $round = new WhichShinyGameRound(1, new WhichShinyGamePokemon('ivysaur', 'Ivysaur', 'Herbizarre', 'Ivysaur', 'Herbizarre', '', '', 2, 'ivysaur'), 1);
        $object = new WhichShinyGameState($game, $round);

        $this->assertSame($game, $object->getGame());
        $this->assertSame($round, $object->getRound());
    }
}
