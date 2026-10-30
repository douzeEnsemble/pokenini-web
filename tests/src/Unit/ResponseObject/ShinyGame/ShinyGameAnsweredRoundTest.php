<?php

declare(strict_types=1);

namespace App\Tests\Unit\ResponseObject\ShinyGame;

use App\ResponseObject\ShinyGame\ShinyGameAnsweredRound;
use App\ResponseObject\ShinyGame\ShinyGamePokemon;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(ShinyGameAnsweredRound::class)]
final class ShinyGameAnsweredRoundTest extends TestCase
{
    #[Test]
    public function getters(): void
    {
        $pokemon = new ShinyGamePokemon('ivysaur', 'Ivysaur', 'Herbizarre', 'Ivysaur', 'Herbizarre', '', '', 2, 'ivysaur');
        $object = new ShinyGameAnsweredRound(4, $pokemon, false, true, false);

        $this->assertSame(4, $object->getPosition());
        $this->assertSame($pokemon, $object->getPokemon());
        $this->assertFalse($object->isCorrect());
        $this->assertTrue($object->isShiny());
        $this->assertFalse($object->isChosenShiny());
    }
}
