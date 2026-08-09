<?php

declare(strict_types=1);

namespace App\Tests\Unit\ResponseObject\Election;

use App\ResponseObject\Common\PokemonCredit;
use App\ResponseObject\Election\TopPokemon;
use App\ResponseObject\Election\TopPokemonGameBundles;
use App\ResponseObject\Election\TopPokemonInfo;
use App\ResponseObject\Election\TopPokemonLabels;
use App\ResponseObject\Election\TopPokemonScore;
use App\ResponseObject\Election\TopPokemonTypes;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(TopPokemon::class)]
final class TopPokemonTest extends TestCase
{
    #[Test]
    public function flattenedGettersAndCredits(): void
    {
        $smallRegular = new PokemonCredit(credit: 'PokéSprite - https://github.com/msikma/pokesprite');
        $bigShiny = new PokemonCredit(credit: 'PokemonDB - https://pokemondb.net/sprites/bulbasaur-shiny');

        $info = new TopPokemonInfo(
            'bulbasaur',
            new TopPokemonLabels('Bulbasaur', 'Bulbizarre', 'Bulbasaur', 'Bulbizarre', null, null),
            1,
            null,
            'bulbasaur',
            0,
            null,
            null,
            null,
            new TopPokemonGameBundles([], []),
            $smallRegular,
            null,
            null,
            $bigShiny,
        );

        $topPokemon = new TopPokemon(
            $info,
            null,
            new TopPokemonTypes(null, null),
            new TopPokemonScore(1, false),
        );

        $this->assertSame('bulbasaur', $topPokemon->getPokemonIcon());
        $this->assertSame('Bulbasaur', $topPokemon->getPokemonName());
        $this->assertSame('Bulbizarre', $topPokemon->getPokemonFrenchName());
        $this->assertSame($smallRegular, $topPokemon->getPokemonSmallRegularCredit());
        $this->assertNull($topPokemon->getPokemonSmallShinyCredit());
        $this->assertNull($topPokemon->getPokemonBigRegularCredit());
        $this->assertSame($bigShiny, $topPokemon->getPokemonBigShinyCredit());
    }
}
