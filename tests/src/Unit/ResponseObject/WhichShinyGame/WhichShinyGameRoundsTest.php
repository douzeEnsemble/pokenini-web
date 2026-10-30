<?php

declare(strict_types=1);

namespace App\Tests\Unit\ResponseObject\WhichShinyGame;

use App\ResponseObject\WhichShinyGame\WhichShinyGame;
use App\ResponseObject\WhichShinyGame\WhichShinyGameAnsweredRound;
use App\ResponseObject\WhichShinyGame\WhichShinyGameDexSummary;
use App\ResponseObject\WhichShinyGame\WhichShinyGamePokemon;
use App\ResponseObject\WhichShinyGame\WhichShinyGameRounds;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(WhichShinyGameRounds::class)]
final class WhichShinyGameRoundsTest extends TestCase
{
    #[Test]
    public function getters(): void
    {
        $game = new WhichShinyGame('g1', new WhichShinyGameDexSummary('demo', 'Demo', 'Démo'), 'completed', 3, 3, 1, '2026-10-20T10:00:00+00:00', null, null);
        $rounds = [new WhichShinyGameAnsweredRound(4, new WhichShinyGamePokemon('ivysaur', 'Ivysaur', 'Herbizarre', 'Ivysaur', 'Herbizarre', '', '', 2, 'ivysaur'), false, 2, 1)];
        $object = new WhichShinyGameRounds($game, $rounds);

        $this->assertSame($game, $object->getGame());
        $this->assertSame($rounds, $object->getRounds());
    }
}
