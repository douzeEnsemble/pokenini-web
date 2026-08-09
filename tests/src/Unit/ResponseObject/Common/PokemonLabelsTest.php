<?php

declare(strict_types=1);

namespace App\Tests\Unit\ResponseObject\Common;

use App\ResponseObject\Common\PokemonLabels;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(PokemonLabels::class)]
final class PokemonLabelsTest extends TestCase
{
    #[Test]
    public function getters(): void
    {
        $labels = new PokemonLabels(
            name: 'Mega Charizard Y',
            frenchName: 'Méga Dracaufeu Y',
            simplifiedName: 'Charizard',
            simplifiedFrenchName: 'Dracaufeu',
            formsLabel: 'Mega Y',
            formsFrenchLabel: 'Méga Y',
        );

        $this->assertSame('Mega Charizard Y', $labels->getName());
        $this->assertSame('Méga Dracaufeu Y', $labels->getFrenchName());
        $this->assertSame('Charizard', $labels->getSimplifiedName());
        $this->assertSame('Dracaufeu', $labels->getSimplifiedFrenchName());
        $this->assertSame('Mega Y', $labels->getFormsLabel());
        $this->assertSame('Méga Y', $labels->getFormsFrenchLabel());
    }

    #[Test]
    public function nullableFields(): void
    {
        $labels = new PokemonLabels(
            name: 'Bulbasaur',
            frenchName: 'Bulbizarre',
            simplifiedName: null,
            simplifiedFrenchName: null,
            formsLabel: null,
            formsFrenchLabel: null,
        );

        $this->assertNull($labels->getSimplifiedName());
        $this->assertNull($labels->getSimplifiedFrenchName());
        $this->assertNull($labels->getFormsLabel());
        $this->assertNull($labels->getFormsFrenchLabel());
    }
}
