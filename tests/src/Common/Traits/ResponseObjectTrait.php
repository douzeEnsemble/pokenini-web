<?php

declare(strict_types=1);

namespace App\Tests\Common\Traits;

use App\ResponseObject\Album\Album;
use App\ResponseObject\Album\Dex;
use App\ResponseObject\Album\DexFlags;
use App\ResponseObject\Album\DexRegion;
use App\ResponseObject\Album\Pokedex;
use App\ResponseObject\Album\Report;
use App\ResponseObject\Album\ReportDetail;
use App\ResponseObject\Common\GameBundlesGroup;
use App\ResponseObject\Common\Pokemon;
use App\ResponseObject\Common\PokemonData;
use App\ResponseObject\Common\PokemonForms;
use App\ResponseObject\Common\PokemonLabels;
use App\ResponseObject\Common\PokemonTypes;
use App\ResponseObject\Election\ElectionList;
use App\ResponseObject\Election\TopPokemon;
use App\ResponseObject\Election\TopPokemonGameBundles;
use App\ResponseObject\Election\TopPokemonInfo;
use App\ResponseObject\Election\TopPokemonLabels;
use App\ResponseObject\Election\TopPokemonScore;
use App\ResponseObject\Election\TopPokemonSlugRef;
use App\ResponseObject\Election\TopPokemonTypes;
use App\ResponseObject\Label\CatchState;
use App\ResponseObject\Label\CategoryForm;
use App\ResponseObject\Label\Collection;
use App\ResponseObject\Label\GameBundle;
use App\ResponseObject\Label\Generation;
use App\ResponseObject\Label\Labels;
use App\ResponseObject\Label\RegionalForm;
use App\ResponseObject\Label\SpecialForm;
use App\ResponseObject\Label\Type;
use App\ResponseObject\Label\VariantForm;

trait ResponseObjectTrait
{
    protected function getStubLabels(): Labels
    {
        $count = 1;

        $catchState = new CatchState('Toto', 'Tautaux', 'toto', '#blouge');
        $catchStates = array_fill(0, $count, $catchState);
        ++$count;

        $type = new Type('Toto', 'Tautaux', 'toto', '#blouge');
        $types = array_fill(0, $count, $type);
        ++$count;

        $categoryForm = new CategoryForm('Toto', 'Tautaux', 'toto');
        $categoryForms = array_fill(0, $count, $categoryForm);
        ++$count;

        $regionalForm = new RegionalForm('Toto', 'Tautaux', 'toto');
        $regionalForms = array_fill(0, $count, $regionalForm);
        ++$count;

        $specialForm = new SpecialForm('Toto', 'Tautaux', 'toto');
        $specialForms = array_fill(0, $count, $specialForm);
        ++$count;

        $variantForm = new VariantForm('Toto', 'Tautaux', 'toto');
        $variantForms = array_fill(0, $count, $variantForm);
        ++$count;

        $gameBundle = new GameBundle('Toto', 'Tautaux', 'toto', new Generation('gen_y'));
        $gameBundles = array_fill(0, $count, $gameBundle);
        ++$count;

        $collection = new Collection('Toto', 'Tautaux', 'toto', 1);
        $collections = array_fill(0, $count, $collection);

        return new Labels(
            $catchStates,
            $types,
            $categoryForms,
            $regionalForms,
            $specialForms,
            $variantForms,
            $gameBundles,
            $collections,
        );
    }

    protected function getStubAlbum(): Album
    {
        return new Album(
            new Pokedex(
                new Dex(
                    'stubby',
                    'stub',
                    'Stub',
                    'Bout',
                    new DexFlags(
                        isShiny: true,
                        isPrivate: false,
                        isOnHome: true,
                        isDisplayForm: true,
                        isReleased: true,
                        isPremium: false,
                        isCustom: true,
                    ),
                    null,
                    new DexRegion('South', 'Sud'),
                    'list',
                    'Stub of south',
                    'Bout du Sud',
                    '546.46545',
                ),
                [
                    $this->getStubPokemon(),
                ],
                new Report(
                    3,
                    2,
                    1,
                    [
                        new ReportDetail(new CatchState('No', 'Non', 'no', '#e57373'), 1),
                        new ReportDetail(new CatchState('Yes', 'Oui', 'yes', '#66bb6a'), 2),
                    ]
                ),
                new Report(
                    1,
                    0,
                    1,
                    [
                        new ReportDetail(new CatchState('No', 'Non', 'no', '#e57373'), 1),
                        new ReportDetail(new CatchState('Yes', 'Oui', 'yes', '#66bb6a'), 0),
                    ]
                ),
            ),
            [
                't1' => [
                    'fire',
                    'water',
                ],
            ],
        );
    }

    protected function getStubAlbumEmpty(): Album
    {
        return new Album(
            new Pokedex(
                null,
                [],
                new Report(null, null, null, []),
                new Report(null, null, null, []),
            ),
            [],
        );
    }

    protected function getStubPokemon(): Pokemon
    {
        return new Pokemon(
            new PokemonData(
                slug: 'bulbasaur',
                labels: new PokemonLabels(
                    name: 'Bulbasaur',
                    frenchName: 'Bulbizarre',
                    simplifiedName: 'Bulbasaur',
                    simplifiedFrenchName: 'Bulbizarre',
                    formsLabel: 'Starter',
                    formsFrenchLabel: 'Starter',
                ),
                nationalDexNumber: 1,
                regionalDexNumber: null,
                icon: 'bulbasaur',
                familyOrder: 0,
                familyLead: null,
                originalGameBundle: null,
                orderNumber: '9999-0001-000',
                gameBundles: new GameBundlesGroup(normal: [], shiny: []),
            ),
            new CatchState('starter', 'Starter', 'starter', '#00ff00'),
            new PokemonForms(
                new CategoryForm('Toto', 'Tautaux', 'toto'),
                null,
                null,
                null,
            ),
            new PokemonTypes(
                new Type('grass', 'Grass', 'Plante', '#00ff00'),
                new Type('poison', 'Poison', 'Poison', '#ff00ff'),
            ),
        );
    }

    protected function getStubTopPokemon(): TopPokemon
    {
        return new TopPokemon(
            new TopPokemonInfo(
                'bulbasaur',
                new TopPokemonLabels('Bulbasaur', 'Bulbizarre', 'Bulbasaur', 'Bulbizarre', null, null),
                1,
                null,
                'bulbasaur',
                'bulbasaur',
                1,
                new TopPokemonSlugRef('bulbasaur'),
                null,
                null,
                new TopPokemonGameBundles([], []),
            ),
            null,                          // forms: null is valid; TopPokemonForms not imported (unused)
            new TopPokemonTypes(null, null),
            new TopPokemonScore(1, false),
        );
    }

    protected function getStubElectionList(): ElectionList
    {
        return new ElectionList(
            'vote',
            [
                $this->getStubPokemon(),
                $this->getStubPokemon(),
                $this->getStubPokemon(),
            ]
        );
    }
}
