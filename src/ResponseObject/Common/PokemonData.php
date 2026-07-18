<?php

declare(strict_types=1);

namespace App\ResponseObject\Common;

use Symfony\Component\Serializer\Attribute\SerializedName;

final class PokemonData
{
    /**
     * @SuppressWarnings("PHPMD.ExcessiveParameterList")
     */
    public function __construct(
        #[SerializedName('slug')]
        private readonly string $slug,
        #[SerializedName('labels')]
        private readonly PokemonLabels $labels,
        #[SerializedName('national_dex_number')]
        private readonly int $nationalDexNumber,
        #[SerializedName('regional_dex_number')]
        private readonly ?int $regionalDexNumber,
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
        private readonly GameBundlesGroup $gameBundles,
        #[SerializedName('small_regular_credit')]
        private readonly ?PokemonCredit $smallRegularCredit,
        #[SerializedName('small_shiny_credit')]
        private readonly ?PokemonCredit $smallShinyCredit,
        #[SerializedName('big_regular_credit')]
        private readonly ?PokemonCredit $bigRegularCredit,
        #[SerializedName('big_shiny_credit')]
        private readonly ?PokemonCredit $bigShinyCredit,
    ) {}

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function getName(): string
    {
        return $this->labels->getName();
    }

    public function getFrenchName(): string
    {
        return $this->labels->getFrenchName();
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
        return $this->labels->getSimplifiedName() ?? '';
    }

    public function getFormsLabel(): string
    {
        return $this->labels->getFormsLabel() ?? '';
    }

    public function getSimplifiedFrenchName(): string
    {
        return $this->labels->getSimplifiedFrenchName() ?? '';
    }

    public function getFormsFrenchLabel(): string
    {
        return $this->labels->getFormsFrenchLabel() ?? '';
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
        return $this->gameBundles->getNormal();
    }

    /**
     * @return PokemonSlugRef[]
     */
    public function getGameBundlesShiny(): array
    {
        return $this->gameBundles->getShiny();
    }

    public function getSmallRegularCredit(): ?PokemonCredit
    {
        return $this->smallRegularCredit;
    }

    public function getSmallShinyCredit(): ?PokemonCredit
    {
        return $this->smallShinyCredit;
    }

    public function getBigRegularCredit(): ?PokemonCredit
    {
        return $this->bigRegularCredit;
    }

    public function getBigShinyCredit(): ?PokemonCredit
    {
        return $this->bigShinyCredit;
    }
}
