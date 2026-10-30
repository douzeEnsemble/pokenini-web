<?php

declare(strict_types=1);

namespace App\Tests\Unit\ResponseObject\WhichShinyGame;

use App\ResponseObject\WhichShinyGame\WhichShinyGameAnsweredRound;
use App\ResponseObject\WhichShinyGame\WhichShinyGamePokemon;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(WhichShinyGameAnsweredRound::class)]
final class WhichShinyGameAnsweredRoundTest extends TestCase
{
    #[Test]
    public function getters(): void
    {
        $pokemon = new WhichShinyGamePokemon('ivysaur', 'Ivysaur', 'Herbizarre', 'Ivysaur', 'Herbizarre', '', '', 2, 'ivysaur');
        $object = new WhichShinyGameAnsweredRound(4, $pokemon, false, 2, 1);

        $this->assertSame(4, $object->getPosition());
        $this->assertSame($pokemon, $object->getPokemon());
        $this->assertFalse($object->isCorrect());
        $this->assertSame(2, $object->getShinySlot());
        $this->assertSame(1, $object->getChosenSlot());
    }
}
