<?php

declare(strict_types=1);

namespace App\Service;

use App\ResponseObject\Label\CatchState;
use App\ResponseObject\Label\CategoryForm;
use App\ResponseObject\Label\Collection;
use App\ResponseObject\Label\GameBundle;
use App\ResponseObject\Label\Labels;
use App\ResponseObject\Label\RegionalForm;
use App\ResponseObject\Label\SpecialForm;
use App\ResponseObject\Label\Type;
use App\ResponseObject\Label\VariantForm;
use App\Service\Back\GetLabelsService as BackGetLabelsService;

class GetLabelsService
{
    private ?Labels $labels = null;

    public function __construct(
        private readonly BackGetLabelsService $getService,
    ) {}

    /**
     * @return array<int, CatchState>
     */
    public function getCatchStates(): array
    {
        return $this->getLabels()->getCatchStates();
    }

    /**
     * @return array<int, Type>
     */
    public function getTypes(): array
    {
        return $this->getLabels()->getTypes();
    }

    /**
     * @return array<int, CategoryForm>
     */
    public function getFormsCategory(): array
    {
        return $this->getLabels()->getCategoryForms();
    }

    /**
     * @return array<int, RegionalForm>
     */
    public function getFormsRegional(): array
    {
        return $this->getLabels()->getRegionalForms();
    }

    /**
     * @return array<int, SpecialForm>
     */
    public function getFormsSpecial(): array
    {
        return $this->getLabels()->getSpecialForms();
    }

    /**
     * @return array<int, VariantForm>
     */
    public function getFormsVariant(): array
    {
        return $this->getLabels()->getVariantForms();
    }

    /**
     * @return array<int, GameBundle>
     */
    public function getGameBundles(): array
    {
        return $this->getLabels()->getGameBundles();
    }

    /**
     * @return array<int, Collection>
     */
    public function getCollections(): array
    {
        return $this->getLabels()->getCollections();
    }

    private function getLabels(): Labels
    {
        if (null === $this->labels) {
            $this->labels = $this->getService->get();
        }

        return $this->labels;
    }
}
