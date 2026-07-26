<?php

declare(strict_types=1);

namespace App\Tests\Unit\ResponseObject\Common;

use App\ResponseObject\Common\GameBundlesGroup;
use App\ResponseObject\Common\PokemonCredit;
use App\ResponseObject\Common\PokemonData;
use App\ResponseObject\Common\PokemonLabels;
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
            labels: new PokemonLabels(
                name: 'Mega Charizard Y',
                frenchName: 'Méga Dracaufeu Y',
                simplifiedName: 'Charizard',
                simplifiedFrenchName: 'Dracaufeu',
                formsLabel: 'Mega Y',
                formsFrenchLabel: 'Méga Y',
            ),
            nationalDexNumber: 6,
            regionalDexNumber: null,
            icon: 'charizard-mega-y',
            familyOrder: 4,
            familyLead: $familyLead,
            originalGameBundle: $originalGameBundle,
            orderNumber: '9999-0006-004',
            gameBundles: new GameBundlesGroup(normal: [$bundle1], shiny: [$bundle2]),
            smallRegularCredit: null,
            smallShinyCredit: null,
            bigRegularCredit: null,
            bigShinyCredit: null,
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
            labels: new PokemonLabels(
                name: 'Bulbasaur',
                frenchName: 'Bulbizarre',
                simplifiedName: 'Bulbasaur',
                simplifiedFrenchName: 'Bulbizarre',
                formsLabel: '',
                formsFrenchLabel: '',
            ),
            nationalDexNumber: 1,
            regionalDexNumber: 1,
            icon: 'bulbasaur',
            familyOrder: 0,
            familyLead: null,
            originalGameBundle: null,
            orderNumber: '0001-0001-000',
            gameBundles: new GameBundlesGroup(normal: [], shiny: []),
            smallRegularCredit: null,
            smallShinyCredit: null,
            bigRegularCredit: null,
            bigShinyCredit: null,
        );

        $this->assertNull($data->getFamilyLead());
        $this->assertNull($data->getOriginalGameBundle());
        $this->assertSame(1, $data->getRegionalDexNumber());
    }

    public function testNullableLabels(): void
    {
        $data = new PokemonData(
            slug: 'bulbasaur',
            labels: new PokemonLabels(
                name: 'Bulbasaur',
                frenchName: 'Bulbizarre',
                simplifiedName: null,
                simplifiedFrenchName: null,
                formsLabel: null,
                formsFrenchLabel: null,
            ),
            nationalDexNumber: 1,
            regionalDexNumber: null,
            icon: 'bulbasaur',
            familyOrder: 0,
            familyLead: null,
            originalGameBundle: null,
            orderNumber: '0001-0001-000',
            gameBundles: new GameBundlesGroup(normal: [], shiny: []),
            smallRegularCredit: null,
            smallShinyCredit: null,
            bigRegularCredit: null,
            bigShinyCredit: null,
        );

        $this->assertSame('', $data->getSimplifiedName());
        $this->assertSame('', $data->getSimplifiedFrenchName());
        $this->assertSame('', $data->getFormsLabel());
        $this->assertSame('', $data->getFormsFrenchLabel());
    }

    public function testCredits(): void
    {
        $smallRegular = new PokemonCredit(credit: 'PokéSprite - https://github.com/msikma/pokesprite');
        $bigShiny = new PokemonCredit(credit: 'PokemonDB - https://pokemondb.net/sprites/bulbasaur-shiny');

        $data = new PokemonData(
            slug: 'bulbasaur',
            labels: new PokemonLabels(
                name: 'Bulbasaur',
                frenchName: 'Bulbizarre',
                simplifiedName: 'Bulbasaur',
                simplifiedFrenchName: 'Bulbizarre',
                formsLabel: '',
                formsFrenchLabel: '',
            ),
            nationalDexNumber: 1,
            regionalDexNumber: null,
            icon: 'bulbasaur',
            familyOrder: 0,
            familyLead: null,
            originalGameBundle: null,
            orderNumber: '0001-0001-000',
            gameBundles: new GameBundlesGroup(normal: [], shiny: []),
            smallRegularCredit: $smallRegular,
            smallShinyCredit: null,
            bigRegularCredit: null,
            bigShinyCredit: $bigShiny,
        );

        $this->assertSame($smallRegular, $data->getSmallRegularCredit());
        $this->assertNull($data->getSmallShinyCredit());
        $this->assertNull($data->getBigRegularCredit());
        $this->assertSame($bigShiny, $data->getBigShinyCredit());
    }
}
