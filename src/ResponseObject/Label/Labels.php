<?php

declare(strict_types=1);

namespace App\ResponseObject\Label;

use Symfony\Component\Serializer\Attribute\SerializedName;

final class Labels
{
    /**
     * @param CatchState[]   $catchStates
     * @param Type[]         $types
     * @param CategoryForm[] $categoryForms
     * @param RegionalForm[] $regionalForms
     * @param SpecialForm[]  $specialForms
     * @param VariantForm[]  $variantForms
     * @param GameBundle[]   $gameBundles
     * @param Collection[]   $collections
     */
    public function __construct(
        #[SerializedName('catch_states')]
        private readonly array $catchStates,
        #[SerializedName('types')]
        private readonly array $types,
        #[SerializedName('category_forms')]
        private readonly array $categoryForms,
        #[SerializedName('regional_forms')]
        private readonly array $regionalForms,
        #[SerializedName('special_forms')]
        private readonly array $specialForms,
        #[SerializedName('variant_forms')]
        private readonly array $variantForms,
        #[SerializedName('game_bundles')]
        private readonly array $gameBundles,
        #[SerializedName('collections')]
        private readonly array $collections,
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
