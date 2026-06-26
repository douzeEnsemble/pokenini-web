<?php

declare(strict_types=1);

namespace App\Tests\Unit\ResponseObject\Common;

use App\ResponseObject\Common\PokemonTypes;
use App\ResponseObject\Label\Type;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(PokemonTypes::class)]
final class PokemonTypesTest extends TestCase
{
    public function testGetters(): void
    {
        $primary = new Type('Grass', 'Plante', 'grass', '#78C850');
        $secondary = new Type('Poison', 'Poison', 'poison', '#A040A0');

        $types = new PokemonTypes($primary, $secondary);

        $this->assertSame($primary, $types->getPrimary());
        $this->assertSame($secondary, $types->getSecondary());
    }

    public function testNullableSecondary(): void
    {
        $primary = new Type('Fire', 'Feu', 'fire', '#FF9D55');
        $types = new PokemonTypes($primary, null);

        $this->assertSame($primary, $types->getPrimary());
        $this->assertNull($types->getSecondary());
    }
}
