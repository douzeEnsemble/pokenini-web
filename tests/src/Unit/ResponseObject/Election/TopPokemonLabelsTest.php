<?php

declare(strict_types=1);

namespace App\Tests\Unit\ResponseObject\Election;

use App\ResponseObject\Election\TopPokemonLabels;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(TopPokemonLabels::class)]
final class TopPokemonLabelsTest extends TestCase
{
    public function testConstructor(): void
    {
        $object = new TopPokemonLabels('Mega Venusaur', 'Mega Florizarre', 'Venusaur', 'Florizarre', 'Mega', 'Mega');

        $this->assertSame('Mega Venusaur', $object->getName());
        $this->assertSame('Mega Florizarre', $object->getFrenchName());
        $this->assertSame('Venusaur', $object->getSimplifiedName());
        $this->assertSame('Florizarre', $object->getSimplifiedFrenchName());
        $this->assertSame('Mega', $object->getFormsLabel());
        $this->assertSame('Mega', $object->getFormsFrenchLabel());
    }

    public function testNullForms(): void
    {
        $object = new TopPokemonLabels('Bulbasaur', 'Bulbizarre', 'Bulbasaur', 'Bulbizarre', null, null);

        $this->assertNull($object->getFormsLabel());
        $this->assertNull($object->getFormsFrenchLabel());
    }
}
