<?php

declare(strict_types=1);

namespace App\Tests\Unit\DTO;

use App\DTO\ElectionPokemonsList;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;

/**
 * @internal
 */
#[CoversClass(ElectionPokemonsList::class)]
class ElectionPokemonsListTest extends TestCase
{
    public function testOk(): void
    {
        $object = new ElectionPokemonsList([
            'type' => 'pick',
            'items' => [
                [
                    'pokemon_slug' => 'pichu',
                    'regional_form_slug' => null,
                    'pokemon_family_order' => 0,
                ],
                [
                    'pokemon_slug' => 'raichu',
                    'regional_form_slug' => null,
                    'pokemon_family_order' => 2,
                ],
            ],
        ]);

        $this->assertSame('pick', $object->type);
        $this->assertSame(
            [
                [
                    'pokemon_slug' => 'pichu',
                    'regional_form_slug' => null,
                    'pokemon_family_order' => 0,
                ],
                [
                    'pokemon_slug' => 'raichu',
                    'regional_form_slug' => null,
                    'pokemon_family_order' => 2,
                ],
            ],
            $object->items
        );
    }

    public function testMissingType(): void
    {
        $this->expectException(MissingOptionsException::class);
        new ElectionPokemonsList([
            'items' => [
                [
                    'pokemon_slug' => 'pichu',
                    'regional_form_slug' => null,
                    'pokemon_family_order' => 0,
                ],
                [
                    'pokemon_slug' => 'raichu',
                    'regional_form_slug' => null,
                    'pokemon_family_order' => 2,
                ],
            ],
        ]);
    }

    public function testWrongType(): void
    {
        $this->expectException(InvalidOptionsException::class);
        new ElectionPokemonsList([
            'type' => 12,
            'items' => [
                [
                    'pokemon_slug' => 'pichu',
                    'regional_form_slug' => null,
                    'pokemon_family_order' => 0,
                ],
                [
                    'pokemon_slug' => 'raichu',
                    'regional_form_slug' => null,
                    'pokemon_family_order' => 2,
                ],
            ],
        ]);
    }

    public function testMissingItems(): void
    {
        $this->expectException(MissingOptionsException::class);
        new ElectionPokemonsList([
            'type' => 'pick',
        ]);
    }

    public function testWrongItems(): void
    {
        $this->expectException(InvalidOptionsException::class);
        new ElectionPokemonsList([
            'type' => 'pick',
            'items' => [
                'pichu',
                'raichu',
            ],
        ]);
    }
}
