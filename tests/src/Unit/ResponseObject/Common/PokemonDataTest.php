<?php

declare(strict_types=1);

namespace App\Tests\Unit\ResponseObject\Common;

use App\ResponseObject\Common\PokemonData;
use App\ResponseObject\Common\PokemonSlugRef;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(PokemonData::class)]
final class PokemonDataTest extends TestCase
{
    public function testGetters(): void
    {
        $familyLead = new PokemonSlugRef('charmander');
        $originalGameBundle = new PokemonSlugRef('xy');
        $bundle1 = new PokemonSlugRef('xy');
        $bundle2 = new PokemonSlugRef('omegarubyalphasapphire');

        $data = new PokemonData(
            slug: 'charizard-mega-y',
            name: 'Mega Charizard Y',
            frenchName: 'Méga Dracaufeu Y',
            nationalDexNumber: 6,
            regionalDexNumber: null,
            simplifiedName: 'Charizard',
            formsLabel: 'Mega Y',
            simplifiedFrenchName: 'Dracaufeu',
            formsFrenchLabel: 'Méga Y',
            icon: 'charizard-mega-y',
            familyOrder: 4,
            familyLead: $familyLead,
            originalGameBundle: $originalGameBundle,
            orderNumber: '9999-0006-004',
            gameBundles: [$bundle1],
            gameBundlesShiny: [$bundle2],
        );

        $this->assertSame('charizard-mega-y', $data->getSlug());
        $this->assertSame('Mega Charizard Y', $data->getName());
        $this->assertSame('Méga Dracaufeu Y', $data->getFrenchName());
        $this->assertSame(6, $data->getNationalDexNumber());
        $this->assertNull($data->getRegionalDexNumber());
        $this->assertSame('Charizard', $data->getSimplifiedName());
        $this->assertSame('Mega Y', $data->getFormsLabel());
        $this->assertSame('Dracaufeu', $data->getSimplifiedFrenchName());
        $this->assertSame('Méga Y', $data->getFormsFrenchLabel());
        $this->assertSame('charizard-mega-y', $data->getIcon());
        $this->assertSame(4, $data->getFamilyOrder());
        $this->assertSame($familyLead, $data->getFamilyLead());
        $this->assertSame($originalGameBundle, $data->getOriginalGameBundle());
        $this->assertSame('9999-0006-004', $data->getOrderNumber());
        $this->assertSame([$bundle1], $data->getGameBundles());
        $this->assertSame([$bundle2], $data->getGameBundlesShiny());
    }

    public function testNullableFamilyLead(): void
    {
        $data = new PokemonData(
            slug: 'bulbasaur',
            name: 'Bulbasaur',
            frenchName: 'Bulbizarre',
            nationalDexNumber: 1,
            regionalDexNumber: 1,
            simplifiedName: 'Bulbasaur',
            formsLabel: '',
            simplifiedFrenchName: 'Bulbizarre',
            formsFrenchLabel: '',
            icon: 'bulbasaur',
            familyOrder: 0,
            familyLead: null,
            originalGameBundle: null,
            orderNumber: '0001-0001-000',
            gameBundles: [],
            gameBundlesShiny: [],
        );

        $this->assertNull($data->getFamilyLead());
        $this->assertNull($data->getOriginalGameBundle());
        $this->assertSame(1, $data->getRegionalDexNumber());
    }
}
