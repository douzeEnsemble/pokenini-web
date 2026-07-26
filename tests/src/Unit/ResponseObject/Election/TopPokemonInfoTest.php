<?php

declare(strict_types=1);

namespace App\Tests\Unit\ResponseObject\Election;

use App\ResponseObject\Common\PokemonCredit;
use App\ResponseObject\Election\TopPokemonGameBundles;
use App\ResponseObject\Election\TopPokemonInfo;
use App\ResponseObject\Election\TopPokemonLabels;
use App\ResponseObject\Election\TopPokemonSlugRef;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(TopPokemonInfo::class)]
final class TopPokemonInfoTest extends TestCase
{
    public function testConstructorAllFields(): void
    {
        $labels = new TopPokemonLabels('Mega Venusaur', 'Mega Florizarre', 'Venusaur', 'Florizarre', 'Mega', 'Mega');
        $familyLead = new TopPokemonSlugRef('bulbasaur');
        $origBundle = new TopPokemonSlugRef('redgreenblueyellow');
        $gameBundles = new TopPokemonGameBundles([new TopPokemonSlugRef('redgreenblueyellow')], []);

        $object = new TopPokemonInfo(
            'venusaur-mega',
            $labels,
            3,
            null,
            'venusaur-mega',
            4,
            $familyLead,
            $origBundle,
            '9999-0003-004',
            $gameBundles,
            null,
            null,
            null,
            null,
        );

        $this->assertSame('venusaur-mega', $object->getSlug());
        $this->assertSame($labels, $object->getLabels());
        $this->assertSame(3, $object->getNationalDexNumber());
        $this->assertNull($object->getRegionalDexNumber());
        $this->assertSame('venusaur-mega', $object->getIcon());
        $this->assertSame(4, $object->getFamilyOrder());
        $this->assertSame($familyLead, $object->getFamilyLead());
        $this->assertSame($origBundle, $object->getOriginalGameBundle());
        $this->assertSame('9999-0003-004', $object->getOrderNumber());
        $this->assertSame($gameBundles, $object->getGameBundles());
    }

    public function testNullableFields(): void
    {
        $labels = new TopPokemonLabels('Blastoise', 'Tortank', 'Blastoise', 'Tortank', null, null);
        $familyLead = new TopPokemonSlugRef('squirtle');
        $gameBundles = new TopPokemonGameBundles([], []);

        $object = new TopPokemonInfo(
            'blastoise-mega',
            $labels,
            9,
            null,
            'blastoise-mega',
            3,
            $familyLead,
            null,
            null,
            $gameBundles,
            null,
            null,
            null,
            null,
        );

        $this->assertNull($object->getOriginalGameBundle());
        $this->assertNull($object->getOrderNumber());
        $this->assertNull($object->getRegionalDexNumber());
    }

    public function testNullFamilyLead(): void
    {
        $labels = new TopPokemonLabels('Bulbasaur', 'Bulbizarre', 'Bulbasaur', 'Bulbizarre', null, null);
        $gameBundles = new TopPokemonGameBundles([], []);

        $object = new TopPokemonInfo(
            'bulbasaur',
            $labels,
            1,
            null,
            'bulbasaur',
            0,
            null,
            null,
            null,
            $gameBundles,
            null,
            null,
            null,
            null,
        );

        $this->assertNull($object->getFamilyLead());
    }

    public function testRegionalDexNumber(): void
    {
        $labels = new TopPokemonLabels('Raichu', 'Raichu', 'Raichu', 'Raichu', null, null);
        $familyLead = new TopPokemonSlugRef('pichu');
        $gameBundles = new TopPokemonGameBundles([], []);

        $object = new TopPokemonInfo(
            'raichu',
            $labels,
            26,
            14,
            'raichu',
            2,
            $familyLead,
            null,
            null,
            $gameBundles,
            null,
            null,
            null,
            null,
        );

        $this->assertSame(14, $object->getRegionalDexNumber());
    }

    public function testCredits(): void
    {
        $labels = new TopPokemonLabels('Bulbasaur', 'Bulbizarre', 'Bulbasaur', 'Bulbizarre', null, null);
        $gameBundles = new TopPokemonGameBundles([], []);
        $smallRegular = new PokemonCredit(credit: 'PokéSprite - https://github.com/msikma/pokesprite');
        $bigShiny = new PokemonCredit(credit: 'PokemonDB - https://pokemondb.net/sprites/bulbasaur-shiny');

        $object = new TopPokemonInfo(
            'bulbasaur',
            $labels,
            1,
            null,
            'bulbasaur',
            0,
            null,
            null,
            null,
            $gameBundles,
            $smallRegular,
            null,
            null,
            $bigShiny,
        );

        $this->assertSame($smallRegular, $object->getSmallRegularCredit());
        $this->assertNull($object->getSmallShinyCredit());
        $this->assertNull($object->getBigRegularCredit());
        $this->assertSame($bigShiny, $object->getBigShinyCredit());
    }
}
