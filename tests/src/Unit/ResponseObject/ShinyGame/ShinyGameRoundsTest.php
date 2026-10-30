<?php

declare(strict_types=1);

namespace App\Tests\Unit\ResponseObject\ShinyGame;

use App\ResponseObject\ShinyGame\ShinyGame;
use App\ResponseObject\ShinyGame\ShinyGameAnsweredRound;
use App\ResponseObject\ShinyGame\ShinyGameDexSummary;
use App\ResponseObject\ShinyGame\ShinyGamePokemon;
use App\ResponseObject\ShinyGame\ShinyGameRounds;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(ShinyGameRounds::class)]
final class ShinyGameRoundsTest extends TestCase
{
    #[Test]
    public function getters(): void
    {
        $game = new ShinyGame('g1', new ShinyGameDexSummary('demo', 'Demo', 'Démo'), 'completed', 3, 3, 1, '2026-10-20T10:00:00+00:00', null, null);
        $rounds = [new ShinyGameAnsweredRound(4, new ShinyGamePokemon('ivysaur', 'Ivysaur', 'Herbizarre', 'Ivysaur', 'Herbizarre', '', '', 2, 'ivysaur'), false, true, false)];
        $object = new ShinyGameRounds($game, $rounds);

        $this->assertSame($game, $object->getGame());
        $this->assertSame($rounds, $object->getRounds());
    }
}
