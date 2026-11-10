<?php

declare(strict_types=1);

namespace App\Tests\Unit\ResponseObject\Election;

use App\ResponseObject\Election\TopPokemonLabels;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(TopPokemonLabels::class)]
final class TopPokemonLabelsTest extends TestCase
{
    #[Test]
    public function constructor(): void
    {
        $object = new TopPokemonLabels('Mega Venusaur', 'Mega Florizarre', 'Venusaur', 'Florizarre', 'Mega', 'Mega');

        $this->assertSame('Mega Venusaur', $object->getName());
        $this->assertSame('Mega Florizarre', $object->getFrenchName());
        $this->assertSame('Venusaur', $object->getSimplifiedName());
        $this->assertSame('Florizarre', $object->getSimplifiedFrenchName());
        $this->assertSame('Mega', $object->getFormsLabel());
        $this->assertSame('Mega', $object->getFormsFrenchLabel());
    }

    #[Test]
    public function nullForms(): void
    {
        $object = new TopPokemonLabels('Bulbasaur', 'Bulbizarre', 'Bulbasaur', 'Bulbizarre', null, null);

        $this->assertNull($object->getFormsLabel());
        $this->assertNull($object->getFormsFrenchLabel());
    }

    #[Test]
    public function legacyMaps(): void
    {
        $object = new TopPokemonLabels('Mega Venusaur', 'Mega Florizarre', 'Venusaur', 'Florizarre', 'Mega', 'Méga');

        $this->assertSame(['en' => 'Mega Venusaur', 'fr' => 'Mega Florizarre'], $object->getNames());
        $this->assertSame(['en' => 'Venusaur', 'fr' => 'Florizarre'], $object->getSimplifiedNames());
        $this->assertSame(['en' => 'Mega', 'fr' => 'Méga'], $object->getFormsLabels());
    }

    #[Test]
    public function legacyWithoutOptionalFields(): void
    {
        $object = new TopPokemonLabels('Bulbasaur', 'Bulbizarre');

        $this->assertSame('', $object->getSimplifiedName());
        $this->assertSame('', $object->getSimplifiedFrenchName());
        $this->assertSame(['en' => '', 'fr' => ''], $object->getFormsLabels());
    }

    #[Test]
    public function byLocale(): void
    {
        $object = new TopPokemonLabels(
            formsLabel: 'Méga',
            names: ['en' => 'Mega Venusaur', 'fr' => 'Mega Florizarre', 'de' => 'Mega-Bisaflor'],
            simplifiedNames: ['en' => 'Venusaur', 'fr' => 'Florizarre', 'de' => 'Bisaflor'],
        );

        $this->assertSame('Mega Venusaur', $object->getName());
        $this->assertSame('Mega Florizarre', $object->getFrenchName());
        $this->assertSame(['en' => 'Mega Venusaur', 'fr' => 'Mega Florizarre', 'de' => 'Mega-Bisaflor'], $object->getNames());
        $this->assertSame('Venusaur', $object->getSimplifiedName());
        $this->assertSame('Florizarre', $object->getSimplifiedFrenchName());
        $this->assertSame(['en' => 'Venusaur', 'fr' => 'Florizarre', 'de' => 'Bisaflor'], $object->getSimplifiedNames());
        $this->assertSame(['*' => 'Méga'], $object->getFormsLabels());
    }

    #[Test]
    public function byLocaleWithoutFormsLabel(): void
    {
        $object = new TopPokemonLabels(names: ['en' => 'Bulbasaur'], simplifiedNames: []);

        $this->assertSame('', $object->getFrenchName());
        $this->assertSame('', $object->getSimplifiedName());
        $this->assertSame([], $object->getFormsLabels());
    }
}
