<?php

declare(strict_types=1);

namespace App\Tests\Unit\ResponseObject\Election;

use App\ResponseObject\Election\TopPokemonForms;
use App\ResponseObject\Label\CategoryForm;
use App\ResponseObject\Label\RegionalForm;
use App\ResponseObject\Label\SpecialForm;
use App\ResponseObject\Label\VariantForm;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(TopPokemonForms::class)]
final class TopPokemonFormsTest extends TestCase
{
    public function testAllNull(): void
    {
        $object = new TopPokemonForms(null, null, null, null);

        $this->assertNull($object->getCategory());
        $this->assertNull($object->getRegional());
        $this->assertNull($object->getSpecial());
        $this->assertNull($object->getVariant());
    }

    public function testAllSet(): void
    {
        $category = new CategoryForm('Legendary', 'Légendaire', 'legendary');
        $regional = new RegionalForm('Alolan', "d'Alola", 'alolan');
        $special = new SpecialForm('Mega', 'Mega', 'mega');
        $variant = new VariantForm('Gender', 'Sexe', 'gender');

        $object = new TopPokemonForms($category, $regional, $special, $variant);

        $this->assertSame($category, $object->getCategory());
        $this->assertSame($regional, $object->getRegional());
        $this->assertSame($special, $object->getSpecial());
        $this->assertSame($variant, $object->getVariant());
    }
}
