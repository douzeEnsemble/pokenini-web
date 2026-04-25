<?php

declare(strict_types=1);

namespace App\Tests\Common\Traits;

use App\DTO\ElectionTop;
use App\ResponseObject\Album\Album;
use App\ResponseObject\Album\Dex;
use App\ResponseObject\Album\Pokedex;
use App\ResponseObject\Album\Report;
use App\ResponseObject\Album\ReportDetail;
use App\ResponseObject\Common\Pokemon;
use App\ResponseObject\Election\ElectionList;
use App\ResponseObject\Election\TopPokemon;
use App\ResponseObject\Label\CatchState;
use App\ResponseObject\Label\CategoryForm;
use App\ResponseObject\Label\Collection;
use App\ResponseObject\Label\GameBundle;
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

        $gameBundle = new GameBundle('Toto', 'Tautaux', 'toto', 'gen_y');
        $gameBundles = array_fill(0, $count, $gameBundle);
        ++$count;

        $collection = new Collection('Toto', 'Tautaux', 'toto', 12);
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
                    true,
                    false,
                    true,
                    'list',
                    'South',
                    'Sud',
                    'Stub of south',
                    'Bout du Sud',
                    546.46545,
                    true,
                    true,
                    true,
                ),
                [
                    $this->getStubPokemon(),
                ],
                new Report(
                    3,
                    2,
                    1,
                    [
                        new ReportDetail(
                            'no',
                            'No',
                            'Non',
                            1,
                        ),
                        new ReportDetail(
                            'yes',
                            'Yes',
                            'Oui',
                            2,
                        ),
                    ]
                ),
                new Report(
                    1,
                    0,
                    1,
                    [
                        new ReportDetail(
                            'no',
                            'No',
                            'Non',
                            1,
                        ),
                        new ReportDetail(
                            'yes',
                            'Yes',
                            'Oui',
                            0,
                        ),
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
            'bulbasaur',
            'Bulbasaur',
            1,
            'Bulbasaur',
            '',
            'Bulbizarre',
            'Bulbizarre',
            '',
            'bulbasaur',
            0,
            null,
            'starter',
            'Starter',
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            'grass',
            'Grass',
            'Plante',
            'poison',
            'Poison',
            'Poison',
            '9999-0001-000',
        );
    }

    protected function getStubTopPokemon(): TopPokemon
    {
        return new TopPokemon(
            'bulbasaur',
            'Bulbasaur',
            1,
            'Bulbasaur',
            '',
            'Bulbizarre',
            'Bulbizarre',
            '',
            'bulbasaur',
            0,
            null,
            'starter',
            'Starter',
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            1,
            2,
            false,
        );
    }

    protected function getStubElectionTop(): ElectionTop
    {
        return new ElectionTop([
            $this->getStubTopPokemon(),
            $this->getStubTopPokemon(),
            $this->getStubTopPokemon(),
        ]);
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
