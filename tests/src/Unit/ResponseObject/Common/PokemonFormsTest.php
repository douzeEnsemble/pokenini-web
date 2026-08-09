<?php

declare(strict_types=1);

namespace App\Tests\Unit\ResponseObject\Common;

use App\ResponseObject\Common\PokemonForms;
use App\ResponseObject\Label\CategoryForm;
use App\ResponseObject\Label\RegionalForm;
use App\ResponseObject\Label\SpecialForm;
use App\ResponseObject\Label\VariantForm;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(PokemonForms::class)]
final class PokemonFormsTest extends TestCase
{
    #[Test]
    public function getters(): void
    {
        $category = new CategoryForm('Starter', 'de Départ', 'starter');
        $regional = new RegionalForm('Alolan', "d'Alola", 'alolan');
        $special = new SpecialForm('Mega', 'Mega', 'mega');
        $variant = new VariantForm('Gender', 'Genre', 'gender');

        $forms = new PokemonForms($category, $regional, $special, $variant);

        $this->assertSame($category, $forms->getCategory());
        $this->assertSame($regional, $forms->getRegional());
        $this->assertSame($special, $forms->getSpecial());
        $this->assertSame($variant, $forms->getVariant());
    }

    #[Test]
    public function nullable(): void
    {
        $forms = new PokemonForms(null, null, null, null);

        $this->assertNull($forms->getCategory());
        $this->assertNull($forms->getRegional());
        $this->assertNull($forms->getSpecial());
        $this->assertNull($forms->getVariant());
    }
}
