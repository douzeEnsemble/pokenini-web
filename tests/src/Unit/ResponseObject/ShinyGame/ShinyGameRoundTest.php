<?php

declare(strict_types=1);

namespace App\Tests\Unit\ResponseObject\ShinyGame;

use App\ResponseObject\ShinyGame\ShinyGamePokemon;
use App\ResponseObject\ShinyGame\ShinyGameRound;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(ShinyGameRound::class)]
final class ShinyGameRoundTest extends TestCase
{
    #[Test]
    public function getters(): void
    {
        $pokemon = new ShinyGamePokemon('ivysaur', 'Ivysaur', 'Herbizarre', 'Ivysaur', 'Herbizarre', '', '', 2, 'ivysaur');
        $object = new ShinyGameRound(4, $pokemon, true);

        $this->assertSame(4, $object->getPosition());
        $this->assertSame($pokemon, $object->getPokemon());
        $this->assertSame(true, $object->isShiny());
    }
}
