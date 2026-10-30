<?php

declare(strict_types=1);

namespace App\Tests\Unit\ResponseObject\ColorGame;

use App\ResponseObject\ColorGame\ColorGameAnsweredRound;
use App\ResponseObject\ColorGame\ColorGameChoice;
use App\ResponseObject\ColorGame\ColorGamePokemon;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(ColorGameAnsweredRound::class)]
final class ColorGameAnsweredRoundTest extends TestCase
{
    #[Test]
    public function getters(): void
    {
        $pokemon = new ColorGamePokemon('ivysaur', 'Ivysaur', 'Herbizarre', 'Ivysaur', 'Herbizarre', '', '', 2, 'ivysaur');
        $choices = [new ColorGameChoice(0, ['#000000'], ['black'], ['noir']), new ColorGameChoice(1, ['#FFFFFF'], ['white'], ['blanc'])];
        $object = new ColorGameAnsweredRound(4, $pokemon, true, $choices, 1, 1);

        $this->assertSame(4, $object->getPosition());
        $this->assertSame($pokemon, $object->getPokemon());
        $this->assertTrue($object->isCorrect());
        $this->assertSame($choices, $object->getChoices());
        $this->assertSame(1, $object->getChosenChoice());
        $this->assertSame(1, $object->getCorrectChoice());
    }
}
