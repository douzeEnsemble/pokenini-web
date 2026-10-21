<?php

declare(strict_types=1);

namespace App\Tests\Unit\ResponseObject\ColorGame;

use App\ResponseObject\ColorGame\ColorGameChoice;
use App\ResponseObject\ColorGame\ColorGamePokemon;
use App\ResponseObject\ColorGame\ColorGameRound;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(ColorGameRound::class)]
final class ColorGameRoundTest extends TestCase
{
    #[Test]
    public function getters(): void
    {
        $pokemon = new ColorGamePokemon('ivysaur', 'Ivysaur', 'Herbizarre', 'Ivysaur', 'Herbizarre', '', '', 2, 'ivysaur');
        $choices = [new ColorGameChoice(0, ['#000000'], ['black'], ['noir'])];
        $object = new ColorGameRound(4, $pokemon, $choices);

        $this->assertSame(4, $object->getPosition());
        $this->assertSame($pokemon, $object->getPokemon());
        $this->assertSame($choices, $object->getChoices());
    }
}
