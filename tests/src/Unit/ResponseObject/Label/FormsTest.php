<?php

declare(strict_types=1);

namespace App\Tests\Unit\ResponseObject\Label;

use App\ResponseObject\Label\CategoryForm;
use App\ResponseObject\Label\Forms;
use App\ResponseObject\Label\RegionalForm;
use App\ResponseObject\Label\SpecialForm;
use App\ResponseObject\Label\VariantForm;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(Forms::class)]
final class FormsTest extends TestCase
{
    #[Test]
    public function constructor(): void
    {
        $category = [new CategoryForm('Starter', 'de Départ', 'starter')];
        $regional = [
            new RegionalForm('Alolan', "d'Alola", 'alolan'),
            new RegionalForm('Galarian', 'de Galar', 'galarian'),
        ];
        $special = [
            new SpecialForm('Mega', 'Mega', 'mega'),
            new SpecialForm('Gigamax', 'Gigamax', 'gigamax'),
            new SpecialForm('Shiny', 'Chromatique', 'shiny'),
        ];
        $variant = [
            new VariantForm('Gender', 'Sexe', 'gender'),
            new VariantForm('Size', 'Taille', 'size'),
            new VariantForm('Pattern', 'Motif', 'pattern'),
            new VariantForm('Form', 'Forme', 'form'),
        ];

        $object = new Forms($category, $regional, $special, $variant);

        $this->assertSame($category, $object->getCategory());
        $this->assertSame($regional, $object->getRegional());
        $this->assertSame($special, $object->getSpecial());
        $this->assertSame($variant, $object->getVariant());
    }

    #[Test]
    public function constructorWithAllEmpty(): void
    {
        $object = new Forms([], [], [], []);

        $this->assertSame([], $object->getCategory());
        $this->assertSame([], $object->getRegional());
        $this->assertSame([], $object->getSpecial());
        $this->assertSame([], $object->getVariant());
    }
}
