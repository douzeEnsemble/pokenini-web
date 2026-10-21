<?php

declare(strict_types=1);

namespace App\Tests\Unit\ResponseObject\ColorGame;

use App\ResponseObject\ColorGame\ColorGamePokemon;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(ColorGamePokemon::class)]
final class ColorGamePokemonTest extends TestCase
{
    #[Test]
    public function getters(): void
    {
        $object = new ColorGamePokemon('raichu-alola', 'Alolan Raichu', 'Raichu d\'Alola', 'Raichu', 'Raichu fr', 'Alolan form', 'Forme d\'Alola', 26, 'raichu-alola-icon');

        $this->assertSame('raichu-alola', $object->getSlug());
        $this->assertSame('Alolan Raichu', $object->getName());
        $this->assertSame('Raichu d\'Alola', $object->getFrenchName());
        $this->assertSame('Raichu', $object->getSimplifiedName());
        $this->assertSame('Raichu fr', $object->getSimplifiedFrenchName());
        $this->assertSame('Alolan form', $object->getFormsLabel());
        $this->assertSame('Forme d\'Alola', $object->getFormsFrenchLabel());
        $this->assertSame(26, $object->getNationalDexNumber());
        $this->assertSame('raichu-alola-icon', $object->getIcon());
    }
}
