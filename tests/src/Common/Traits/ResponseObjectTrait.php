<?php

declare(strict_types=1);

namespace App\Tests\Common\Traits;

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
        $catchStates = array_fill(0, $count++, $catchState);

        $type = new Type('Toto', 'Tautaux', 'toto', '#blouge');
        $types = array_fill(0, $count++, $type);

        $categoryForm = new CategoryForm('Toto', 'Tautaux', 'toto');
        $categoryForms = array_fill(0, $count++, $categoryForm);

        $regionalForm = new RegionalForm('Toto', 'Tautaux', 'toto');
        $regionalForms = array_fill(0, $count++, $regionalForm);

        $specialForm = new SpecialForm('Toto', 'Tautaux', 'toto');
        $specialForms = array_fill(0, $count++, $specialForm);

        $variantForm = new VariantForm('Toto', 'Tautaux', 'toto');
        $variantForms = array_fill(0, $count++, $variantForm);

        $gameBundle = new GameBundle('Toto', 'Tautaux', 'toto', 'gen_y');
        $gameBundles = array_fill(0, $count++, $gameBundle);

        $collection = new Collection('Toto', 'Tautaux', 'toto', 12);
        $collections = array_fill(0, $count++, $collection);

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
}
