<?php

declare(strict_types=1);

namespace App\Tests\Unit\ResponseObject\ShinyGame;

use App\ResponseObject\ShinyGame\ShinyGame;
use App\ResponseObject\ShinyGame\ShinyGameDexSummary;
use App\ResponseObject\ShinyGame\ShinyGamePokemon;
use App\ResponseObject\ShinyGame\ShinyGameRound;
use App\ResponseObject\ShinyGame\ShinyGameState;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(ShinyGameState::class)]
final class ShinyGameStateTest extends TestCase
{
    #[Test]
    public function getters(): void
    {
        $game = new ShinyGame('g1', new ShinyGameDexSummary('demo', 'Demo', 'Démo'), 'in_progress', 3, 1, 1, '2026-10-20T10:00:00+00:00', null, null);
        $round = new ShinyGameRound(1, new ShinyGamePokemon('ivysaur', 'Ivysaur', 'Herbizarre', 'Ivysaur', 'Herbizarre', '', '', 2, 'ivysaur'), false);
        $object = new ShinyGameState($game, $round);

        $this->assertSame($game, $object->getGame());
        $this->assertSame($round, $object->getRound());
    }
}
