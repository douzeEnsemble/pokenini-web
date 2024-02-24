<?php

declare(strict_types=1);

namespace App\Service\Api;

class GetLabelsService extends AbstractApiService
{
    public function __construct(
        private readonly GetCatchStatesService $getCatchStatesService,
        private readonly GetTypesService $getTypesService,
        private readonly GetFormsService $getFormsService,
    ) {
    }

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
}
