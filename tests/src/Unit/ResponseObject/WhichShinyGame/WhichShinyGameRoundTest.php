<?php

declare(strict_types=1);

namespace App\Tests\Unit\ResponseObject\WhichShinyGame;

use App\ResponseObject\WhichShinyGame\WhichShinyGamePokemon;
use App\ResponseObject\WhichShinyGame\WhichShinyGameRound;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(WhichShinyGameRound::class)]
final class WhichShinyGameRoundTest extends TestCase
{
    #[Test]
    public function getters(): void
    {
        $pokemon = new WhichShinyGamePokemon('ivysaur', 'Ivysaur', 'Herbizarre', 'Ivysaur', 'Herbizarre', '', '', 2, 'ivysaur');
        $object = new WhichShinyGameRound(4, $pokemon, 2);

        $this->assertSame(4, $object->getPosition());
        $this->assertSame($pokemon, $object->getPokemon());
        $this->assertSame(2, $object->getShinySlot());
    }
}
