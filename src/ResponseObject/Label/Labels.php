<?php

declare(strict_types=1);

namespace App\ResponseObject\Label;

use Symfony\Component\Serializer\Attribute\SerializedName;

final class Labels
{
    /**
     * @param array<int, CatchState>   $catchStates
     * @param array<int, Type>         $types
     * @param array<int, CategoryForm> $categoryForms
     * @param array<int, RegionalForm> $regionalForms
     * @param array<int, SpecialForm>  $specialForms
     * @param array<int, VariantForm>  $variantForms
     * @param array<int, GameBundle>   $gameBundles
     * @param array<int, Collection>   $collections
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
     * @return array<int, CatchState>
     */
    public function getCatchStates(): array
    {
        return $this->catchStates;
    }

    /**
     * @return array<int, Type>
     */
    public function getTypes(): array
    {
        return $this->types;
    }

    public function getForms(): Forms
    {
        return new Forms(
            $this->categoryForms,
            $this->regionalForms,
            $this->specialForms,
            $this->variantForms,
        );
    }

    /**
     * @return array<int, GameBundle>
     */
    public function getGameBundles(): array
    {
        return $this->gameBundles;
    }

    /**
     * @return array<int, Collection>
     */
    public function getCollections(): array
    {
        return $this->collections;
    }
}
