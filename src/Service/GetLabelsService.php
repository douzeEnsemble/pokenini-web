<?php

declare(strict_types=1);

namespace App\Service;

use App\Service\Back\GetCatchStatesService;
use App\Service\Back\GetCollectionsService;
use App\Service\Back\GetFormsService;
use App\Service\Back\GetGameBundlesService;
use App\Service\Back\GetTypesService;

class GetLabelsService
{
    public function __construct(
        private readonly GetCatchStatesService $getCatchStatesService,
        private readonly GetTypesService $getTypesService,
        private readonly GetFormsService $getFormsService,
        private readonly GetGameBundlesService $getGameBundlesService,
        private readonly GetCollectionsService $getCollectionsService,
    ) {}

    /**
     * @return string[][]
     */
    public function getCatchStates(): array
    {
        return $this->getCatchStatesService->get();
    }

    /**
     * @return string[][]
     */
    public function getTypes(): array
    {
        return $this->getTypesService->get();
    }

    /**
     * @return string[][]
     */
    public function getFormsCategory(): array
    {
        return $this->getFormsService->getFormsCategory();
    }

    /**
     * @return string[][]
     */
    public function getFormsRegional(): array
    {
        return $this->getFormsService->getFormsRegional();
    }

    /**
     * @return string[][]
     */
    public function getFormsSpecial(): array
    {
        return $this->getFormsService->getFormsSpecial();
    }

    /**
     * @return string[][]
     */
    public function getFormsVariant(): array
    {
        return $this->getFormsService->getFormsVariant();
    }

    /**
     * @return string[][]
     */
    public function getGameBundles(): array
    {
        return $this->getGameBundlesService->get();
    }

    /**
     * @return string[][]
     */
    public function getCollections(): array
    {
        return $this->getCollectionsService->get();
    }
}
