<?php

declare(strict_types=1);

namespace App\Tests\Unit\ResponseObject\Election;

use App\ResponseObject\Election\TopPokemonTypes;
use App\ResponseObject\Label\Type;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(TopPokemonTypes::class)]
final class TopPokemonTypesTest extends TestCase
{
    #[Test]
    public function bothNull(): void
    {
        $object = new TopPokemonTypes(null, null);

        $this->assertNull($object->getPrimary());
        $this->assertNull($object->getSecondary());
    }

    #[Test]
    public function bothSet(): void
    {
        $primary = new Type('Grass', 'Plante', 'grass', '#78C850');
        $secondary = new Type('Poison', 'Poison', 'poison', '#A040A0');

        $object = new TopPokemonTypes($primary, $secondary);

        $this->assertSame($primary, $object->getPrimary());
        $this->assertSame($secondary, $object->getSecondary());
    }

    #[Test]
    public function onlyPrimary(): void
    {
        $primary = new Type('Normal', 'Normal', 'normal', '#A8A878');
        $object = new TopPokemonTypes($primary, null);

        $this->assertSame($primary, $object->getPrimary());
        $this->assertNull($object->getSecondary());
    }
}
