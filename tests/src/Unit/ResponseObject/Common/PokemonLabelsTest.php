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

    #[Test]
    public function legacyMaps(): void
    {
        $labels = new PokemonLabels(
            name: 'Mega Charizard Y',
            frenchName: 'Méga Dracaufeu Y',
            simplifiedName: 'Charizard',
            simplifiedFrenchName: 'Dracaufeu',
            formsLabel: 'Mega Y',
            formsFrenchLabel: 'Méga Y',
        );

        $this->assertSame(['en' => 'Mega Charizard Y', 'fr' => 'Méga Dracaufeu Y'], $labels->getNames());
        $this->assertSame(['en' => 'Charizard', 'fr' => 'Dracaufeu'], $labels->getSimplifiedNames());
        $this->assertSame(['en' => 'Mega Y', 'fr' => 'Méga Y'], $labels->getFormsLabels());
    }

    #[Test]
    public function legacyWithoutOptionalFields(): void
    {
        $labels = new PokemonLabels(name: 'Bulbasaur', frenchName: 'Bulbizarre');

        $this->assertSame(['en' => '', 'fr' => ''], $labels->getSimplifiedNames());
        $this->assertSame(['en' => '', 'fr' => ''], $labels->getFormsLabels());
    }

    #[Test]
    public function byLocale(): void
    {
        $labels = new PokemonLabels(
            formsLabel: 'Méga Y',
            names: ['en' => 'Mega Charizard Y', 'fr' => 'Méga Dracaufeu Y', 'de' => 'Mega-Glurak Y'],
            simplifiedNames: ['en' => 'Charizard', 'fr' => 'Dracaufeu', 'de' => 'Glurak'],
        );

        $this->assertSame('Mega Charizard Y', $labels->getName());
        $this->assertSame('Méga Dracaufeu Y', $labels->getFrenchName());
        $this->assertSame(['en' => 'Mega Charizard Y', 'fr' => 'Méga Dracaufeu Y', 'de' => 'Mega-Glurak Y'], $labels->getNames());
        $this->assertSame('Charizard', $labels->getSimplifiedName());
        $this->assertSame('Dracaufeu', $labels->getSimplifiedFrenchName());
        $this->assertSame(['en' => 'Charizard', 'fr' => 'Dracaufeu', 'de' => 'Glurak'], $labels->getSimplifiedNames());
        $this->assertSame(['*' => 'Méga Y'], $labels->getFormsLabels());
    }

    #[Test]
    public function byLocaleWithoutFormsLabel(): void
    {
        $labels = new PokemonLabels(names: ['en' => 'Bulbasaur'], simplifiedNames: []);

        $this->assertSame('', $labels->getFrenchName());
        $this->assertNull($labels->getSimplifiedName());
        $this->assertNull($labels->getSimplifiedFrenchName());
        $this->assertSame([], $labels->getFormsLabels());
    }
}
