<?php

declare(strict_types=1);

namespace App\ResponseObject\Album;

use Symfony\Component\Serializer\Attribute\SerializedName;

class Pokemon
{
    public function __construct(
        #[SerializedName('pokemon_slug')]
        private readonly string $pokemonSlug,
        #[SerializedName('pokemon_name')]
        private readonly string $pokemonName,
        #[SerializedName('pokemon_national_dex_number')]
        private readonly int $pokemonNationalDexNumber,
        #[SerializedName('pokemon_simplified_name')]
        private readonly string $pokemonSimplifiedName,
        #[SerializedName('pokemon_forms_label')]
        private readonly string $pokemonFormsLabel,
        #[SerializedName('pokemon_french_name')]
        private readonly string $pokemonFrenchName,
        #[SerializedName('pokemon_simplified_french_name')]
        private readonly string $pokemonSimplifiedFrenchName,
        #[SerializedName('pokemon_forms_french_label')]
        private readonly string $pokemonFormsFrenchLabel,
        #[SerializedName('pokemon_icon')]
        private readonly string $pokemonIcon,
        #[SerializedName('pokemon_family_order')]
        private readonly int $pokemonFamilyOrder,
        #[SerializedName('family_lead_slug')]
        private readonly ?string $familyLeadSlug,
        #[SerializedName('category_form_slug')]
        private readonly ?string $categoryFormSlug,
        #[SerializedName('category_form_name')]
        private readonly ?string $categoryFormName,
        #[SerializedName('regional_form_slug')]
        private readonly ?string $regionalFormSlug,
        #[SerializedName('regional_form_name')]
        private readonly ?string $regionalFormName,
        #[SerializedName('special_form_slug')]
        private readonly ?string $specialFormSlug,
        #[SerializedName('special_form_name')]
        private readonly ?string $specialFormName,
        #[SerializedName('variant_form_slug')]
        private readonly ?string $variantFormSlug,
        #[SerializedName('variant_form_name')]
        private readonly ?string $variantFormName,
        #[SerializedName('catch_state_slug')]
        private readonly ?string $catchStateSlug,
        #[SerializedName('catch_state_name')]
        private readonly ?string $catchStateName,
        #[SerializedName('catch_state_french_name')]
        private readonly ?string $catchStateFrenchName,
        #[SerializedName('pokemon_regional_dex_number')]
        private readonly ?int $pokemonRegionalDexNumber,
        #[SerializedName('primary_type_slug')]
        private readonly string $primaryTypeSlug,
        #[SerializedName('primary_type_name')]
        private readonly string $primaryTypeName,
        #[SerializedName('primary_type_french_name')]
        private readonly string $primaryTypeFrenchName,
        #[SerializedName('secondary_type_slug')]
        private readonly ?string $secondaryTypeSlug,
        #[SerializedName('secondary_type_name')]
        private readonly ?string $secondaryTypeName,
        #[SerializedName('secondary_type_french_name')]
        private readonly ?string $secondaryTypeFrenchName,
        #[SerializedName('pokemon_order_number')]
        private readonly string $pokemonOrderNumber,
    ) {}

    public function getPokemonSlug(): string
    {
        return $this->pokemonSlug;
    }

    public function getPokemonName(): string
    {
        return $this->pokemonName;
    }

    public function getPokemonNationalDexNumber(): int
    {
        return $this->pokemonNationalDexNumber;
    }

    public function getPokemonSimplifiedName(): string
    {
        return $this->pokemonSimplifiedName;
    }

    public function getPokemonFormsLabel(): string
    {
        return $this->pokemonFormsLabel;
    }

    public function getPokemonFrenchName(): string
    {
        return $this->pokemonFrenchName;
    }

    public function getPokemonSimplifiedFrenchName(): string
    {
        return $this->pokemonSimplifiedFrenchName;
    }

    public function getPokemonFormsFrenchLabel(): string
    {
        return $this->pokemonFormsFrenchLabel;
    }

    public function getPokemonIcon(): string
    {
        return $this->pokemonIcon;
    }

    public function getPokemonFamilyOrder(): int
    {
        return $this->pokemonFamilyOrder;
    }

    public function getFamilyLeadSlug(): ?string
    {
        return $this->familyLeadSlug;
    }

    public function getCategoryFormSlug(): ?string
    {
        return $this->categoryFormSlug;
    }

    public function getCategoryFormName(): ?string
    {
        return $this->categoryFormName;
    }

    public function getRegionalFormSlug(): ?string
    {
        return $this->regionalFormSlug;
    }

    public function getRegionalFormName(): ?string
    {
        return $this->regionalFormName;
    }

    public function getSpecialFormSlug(): ?string
    {
        return $this->specialFormSlug;
    }

    public function getSpecialFormName(): ?string
    {
        return $this->specialFormName;
    }

    public function getVariantFormSlug(): ?string
    {
        return $this->variantFormSlug;
    }

    public function getVariantFormName(): ?string
    {
        return $this->variantFormName;
    }

    public function getCatchStateSlug(): ?string
    {
        return $this->catchStateSlug;
    }

    public function getCatchStateName(): ?string
    {
        return $this->catchStateName;
    }

    public function getCatchStateFrenchName(): ?string
    {
        return $this->catchStateFrenchName;
    }

    public function getPokemonRegionalDexNumber(): ?int
    {
        return $this->pokemonRegionalDexNumber;
    }

    public function getPrimaryTypeSlug(): string
    {
        return $this->primaryTypeSlug;
    }

    public function getPrimaryTypeName(): string
    {
        return $this->primaryTypeName;
    }

    public function getPrimaryTypeFrenchName(): string
    {
        return $this->primaryTypeFrenchName;
    }

    public function getSecondaryTypeSlug(): ?string
    {
        return $this->secondaryTypeSlug;
    }

    public function getSecondaryTypeName(): ?string
    {
        return $this->secondaryTypeName;
    }

    public function getSecondaryTypeFrenchName(): ?string
    {
        return $this->secondaryTypeFrenchName;
    }

    public function getPokemonOrderNumber(): string
    {
        return $this->pokemonOrderNumber;
    }
}
