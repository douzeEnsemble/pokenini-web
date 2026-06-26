<?php

declare(strict_types=1);

namespace App\ResponseObject\Common;

use Symfony\Component\Serializer\Attribute\SerializedName;

/**
 * @SuppressWarnings("PHPMD.ExcessiveParameterList")
 */
final class PokemonData
{
    /**
     * @param PokemonSlugRef[] $gameBundles
     * @param PokemonSlugRef[] $gameBundlesShiny
     */
    public function __construct(
        #[SerializedName('slug')]
        private readonly string $slug,
        #[SerializedName('name')]
        private readonly string $name,
        #[SerializedName('french_name')]
        private readonly string $frenchName,
        #[SerializedName('national_dex_number')]
        private readonly int $nationalDexNumber,
        #[SerializedName('regional_dex_number')]
        private readonly ?int $regionalDexNumber,
        #[SerializedName('simplified_name')]
        private readonly string $simplifiedName,
        #[SerializedName('forms_label')]
        private readonly string $formsLabel,
        #[SerializedName('simplified_french_name')]
        private readonly string $simplifiedFrenchName,
        #[SerializedName('forms_french_label')]
        private readonly string $formsFrenchLabel,
        #[SerializedName('icon')]
        private readonly string $icon,
        #[SerializedName('family_order')]
        private readonly int $familyOrder,
        #[SerializedName('family_lead')]
        private readonly ?PokemonSlugRef $familyLead,
        #[SerializedName('original_game_bundle')]
        private readonly ?PokemonSlugRef $originalGameBundle,
        #[SerializedName('order_number')]
        private readonly string $orderNumber,
        #[SerializedName('game_bundles')]
        private readonly array $gameBundles,
        #[SerializedName('game_bundles_shiny')]
        private readonly array $gameBundlesShiny,
    ) {}

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getFrenchName(): string
    {
        return $this->frenchName;
    }

    public function getNationalDexNumber(): int
    {
        return $this->nationalDexNumber;
    }

    public function getRegionalDexNumber(): ?int
    {
        return $this->regionalDexNumber;
    }

    public function getSimplifiedName(): string
    {
        return $this->simplifiedName;
    }

    public function getFormsLabel(): string
    {
        return $this->formsLabel;
    }

    public function getSimplifiedFrenchName(): string
    {
        return $this->simplifiedFrenchName;
    }

    public function getFormsFrenchLabel(): string
    {
        return $this->formsFrenchLabel;
    }

    public function getIcon(): string
    {
        return $this->icon;
    }

    public function getFamilyOrder(): int
    {
        return $this->familyOrder;
    }

    public function getFamilyLead(): ?PokemonSlugRef
    {
        return $this->familyLead;
    }

    public function getOriginalGameBundle(): ?PokemonSlugRef
    {
        return $this->originalGameBundle;
    }

    public function getOrderNumber(): string
    {
        return $this->orderNumber;
    }

    /**
     * @return PokemonSlugRef[]
     */
    public function getGameBundles(): array
    {
        return $this->gameBundles;
    }

    /**
     * @return PokemonSlugRef[]
     */
    public function getGameBundlesShiny(): array
    {
        return $this->gameBundlesShiny;
    }
}
