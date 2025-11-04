<?php

declare(strict_types=1);

namespace App\ResponseObject;

class Labels
{
    /**
     * @param CatchState[] $catchStates
     * @param Type[] $types
     * @param CategoryForm[] $categoryForms
     * @param RegionalForm[] $regionalForms
     * @param SpecialForm[] $specialForms
     * @param VariantForm[] $variantForms
     * @param GameBundle[] $gameBundles
     * @param Collection[] $collections
     */
    public function __construct(
        private array $catchStates,
        private array $types,
        private array $categoryForms,
        private array $regionalForms,
        private array $specialForms,
        private array $variantForms,
        private array $gameBundles,
        private array $collections,
    ) {}

    /**
     * @return CatchState[]
     */
    public function getCatchStates(): array
    {
        return $this->catchStates;
    }

    /**
     * @return Type[]
     */
    public function getTypes(): array
    {
        return $this->types;
    }

    /**
     * @return CategoryForm[]
     */
    public function getCategoryForms(): array
    {
        return $this->categoryForms;
    }

    /**
     * @return RegionalForm[]
     */
    public function getRegionalForms(): array
    {
        return $this->regionalForms;
    }

    /**
     * @return SpecialForm[]
     */
    public function getSpecialForms(): array
    {
        return $this->specialForms;
    }

    /**
     * @return VariantForm[]
     */
    public function getVariantForms(): array
    {
        return $this->variantForms;
    }

    /**
     * @return GameBundle[]
     */
    public function getGameBundles(): array
    {
        return $this->gameBundles;
    }

    /**
     * @return Collection[]
     */
    public function getCollections(): array
    {
        return $this->collections;
    }
}
