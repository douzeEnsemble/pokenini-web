<?php

declare(strict_types=1);

namespace App\ResponseObject\Label;

use Symfony\Component\Serializer\Attribute\SerializedName;

final class Labels
{
    /**
     * @param array<int, CatchState> $catchStates
     * @param array<int, Type>       $types
     * @param array<int, GameBundle> $gameBundles
     * @param array<int, Collection> $collections
     */
    public function __construct(
        #[SerializedName('catch_states')]
        private readonly array $catchStates,
        #[SerializedName('types')]
        private readonly array $types,
        #[SerializedName('forms')]
        private readonly Forms $forms,
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

    /**
     * @return array<int, CategoryForm>
     */
    public function getCategoryForms(): array
    {
        return $this->forms->getCategory();
    }

    /**
     * @return array<int, RegionalForm>
     */
    public function getRegionalForms(): array
    {
        return $this->forms->getRegional();
    }

    /**
     * @return array<int, SpecialForm>
     */
    public function getSpecialForms(): array
    {
        return $this->forms->getSpecial();
    }

    /**
     * @return array<int, VariantForm>
     */
    public function getVariantForms(): array
    {
        return $this->forms->getVariant();
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
