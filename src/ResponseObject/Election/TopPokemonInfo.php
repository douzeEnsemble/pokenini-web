<?php

declare(strict_types=1);

namespace App\ResponseObject\Election;

use App\ResponseObject\Common\PokemonCredit;
use Symfony\Component\Serializer\Attribute\SerializedName;

/**
 * @SuppressWarnings("PHPMD.ExcessiveParameterList")
 */
final class TopPokemonInfo
{
    public function __construct(
        #[SerializedName('slug')]
        private readonly string $slug,
        #[SerializedName('labels')]
        private readonly TopPokemonLabels $labels,
        #[SerializedName('national_dex_number')]
        private readonly int $nationalDexNumber,
        #[SerializedName('regional_dex_number')]
        private readonly ?int $regionalDexNumber,
        #[SerializedName('icon')]
        private readonly string $icon,
        #[SerializedName('family_order')]
        private readonly int $familyOrder,
        #[SerializedName('family_lead')]
        private readonly ?TopPokemonSlugRef $familyLead,
        #[SerializedName('original_game_bundle')]
        private readonly ?TopPokemonSlugRef $originalGameBundle,
        #[SerializedName('order_number')]
        private readonly ?string $orderNumber,
        #[SerializedName('game_bundles')]
        private readonly TopPokemonGameBundles $gameBundles,
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

    public function getLabels(): TopPokemonLabels
    {
        return $this->labels;
    }

    public function getNationalDexNumber(): int
    {
        return $this->nationalDexNumber;
    }

    public function getRegionalDexNumber(): ?int
    {
        return $this->regionalDexNumber;
    }

    public function getIcon(): string
    {
        return $this->icon;
    }

    public function getFamilyOrder(): int
    {
        return $this->familyOrder;
    }

    public function getFamilyLead(): ?TopPokemonSlugRef
    {
        return $this->familyLead;
    }

    public function getOriginalGameBundle(): ?TopPokemonSlugRef
    {
        return $this->originalGameBundle;
    }

    public function getOrderNumber(): ?string
    {
        return $this->orderNumber;
    }

    public function getGameBundles(): TopPokemonGameBundles
    {
        return $this->gameBundles;
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
