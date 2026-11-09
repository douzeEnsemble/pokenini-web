<?php

declare(strict_types=1);

namespace App\ResponseObject\Common;

use App\ResponseObject\Label\CatchState;
use Symfony\Component\Serializer\Attribute\SerializedName;

final class Pokemon
{
    public function __construct(
        #[SerializedName('pokemon')]
        private readonly PokemonData $pokemon,
        #[SerializedName('catch_state')]
        private readonly ?CatchState $catchState,
        #[SerializedName('forms')]
        private readonly ?PokemonForms $forms,
        #[SerializedName('types')]
        private readonly PokemonTypes $types,
    ) {}

    public function getPokemonSlug(): string
    {
        return $this->pokemon->getSlug();
    }

    public function getPokemonName(): string
    {
        return $this->pokemon->getName();
    }

    public function getPokemonFrenchName(): string
    {
        return $this->pokemon->getFrenchName();
    }

    /**
     * @return array<string, string> the name of the Pokémon indexed by locale
     */
    public function getPokemonNames(): array
    {
        return $this->pokemon->getNames();
    }

    /**
     * @return array<string, string> the simplified name of the Pokémon indexed by locale
     */
    public function getPokemonSimplifiedNames(): array
    {
        return $this->pokemon->getSimplifiedNames();
    }

    /**
     * @return array<string, string> the forms label of the Pokémon indexed by locale
     */
    public function getPokemonFormsLabels(): array
    {
        return $this->pokemon->getFormsLabels();
    }

    /**
     * @return null|array<string, string> the name of the catch state indexed by locale
     */
    public function getCatchStateNames(): ?array
    {
        return $this->catchState?->getNames();
    }

    /**
     * @return null|array<string, string> the name of the primary type indexed by locale
     */
    public function getPrimaryTypeNames(): ?array
    {
        return $this->types->getPrimary()?->getNames();
    }

    /**
     * @return null|array<string, string> the name of the secondary type indexed by locale
     */
    public function getSecondaryTypeNames(): ?array
    {
        return $this->types->getSecondary()?->getNames();
    }

    public function getPokemonNationalDexNumber(): int
    {
        return $this->pokemon->getNationalDexNumber();
    }

    public function getPokemonRegionalDexNumber(): ?int
    {
        return $this->pokemon->getRegionalDexNumber();
    }

    public function getPokemonSimplifiedName(): string
    {
        return $this->pokemon->getSimplifiedName();
    }

    public function getPokemonFormsLabel(): string
    {
        return $this->pokemon->getFormsLabel();
    }

    public function getPokemonSimplifiedFrenchName(): string
    {
        return $this->pokemon->getSimplifiedFrenchName();
    }

    public function getPokemonFormsFrenchLabel(): string
    {
        return $this->pokemon->getFormsFrenchLabel();
    }

    public function getPokemonIcon(): string
    {
        return $this->pokemon->getIcon();
    }

    public function getPokemonSmallRegularCredit(): ?PokemonCredit
    {
        return $this->pokemon->getSmallRegularCredit();
    }

    public function getPokemonSmallShinyCredit(): ?PokemonCredit
    {
        return $this->pokemon->getSmallShinyCredit();
    }

    public function getPokemonBigRegularCredit(): ?PokemonCredit
    {
        return $this->pokemon->getBigRegularCredit();
    }

    public function getPokemonBigShinyCredit(): ?PokemonCredit
    {
        return $this->pokemon->getBigShinyCredit();
    }

    public function getPokemonFamilyOrder(): int
    {
        return $this->pokemon->getFamilyOrder();
    }

    public function getFamilyLeadSlug(): ?string
    {
        return $this->pokemon->getFamilyLead()?->getSlug();
    }

    public function getPokemonOrderNumber(): string
    {
        return $this->pokemon->getOrderNumber();
    }

    public function getCatchStateSlug(): ?string
    {
        return $this->catchState?->getSlug();
    }

    public function getCatchStateName(): ?string
    {
        return $this->catchState?->getName();
    }

    public function getCatchStateFrenchName(): ?string
    {
        return $this->catchState?->getFrenchName();
    }

    public function getCategoryFormSlug(): ?string
    {
        return $this->forms?->getCategory()?->getSlug();
    }

    public function getCategoryFormName(): ?string
    {
        return $this->forms?->getCategory()?->getName();
    }

    public function getRegionalFormSlug(): ?string
    {
        return $this->forms?->getRegional()?->getSlug();
    }

    public function getRegionalFormName(): ?string
    {
        return $this->forms?->getRegional()?->getName();
    }

    public function getSpecialFormSlug(): ?string
    {
        return $this->forms?->getSpecial()?->getSlug();
    }

    public function getSpecialFormName(): ?string
    {
        return $this->forms?->getSpecial()?->getName();
    }

    public function getVariantFormSlug(): ?string
    {
        return $this->forms?->getVariant()?->getSlug();
    }

    public function getVariantFormName(): ?string
    {
        return $this->forms?->getVariant()?->getName();
    }

    public function getPrimaryTypeSlug(): ?string
    {
        return $this->types->getPrimary()?->getSlug();
    }

    public function getPrimaryTypeName(): ?string
    {
        return $this->types->getPrimary()?->getName();
    }

    public function getPrimaryTypeFrenchName(): ?string
    {
        return $this->types->getPrimary()?->getFrenchName();
    }

    public function getSecondaryTypeSlug(): ?string
    {
        return $this->types->getSecondary()?->getSlug();
    }

    public function getSecondaryTypeName(): ?string
    {
        return $this->types->getSecondary()?->getName();
    }

    public function getSecondaryTypeFrenchName(): ?string
    {
        return $this->types->getSecondary()?->getFrenchName();
    }
}
