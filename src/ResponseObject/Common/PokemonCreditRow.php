<?php

declare(strict_types=1);

namespace App\ResponseObject\Common;

use Symfony\Component\Serializer\Attribute\SerializedName;

final class PokemonCreditRow
{
    public function __construct(
        #[SerializedName('pokemon_slug')]
        private readonly string $pokemonSlug,
        #[SerializedName('pokemon_name')]
        private readonly string $pokemonName,
        #[SerializedName('pokemon_french_name')]
        private readonly string $pokemonFrenchName,
        #[SerializedName('pokemon_icon')]
        private readonly string $pokemonIcon,
        #[SerializedName('small_regular_credit')]
        private readonly ?PokemonCredit $smallRegularCredit,
        #[SerializedName('small_shiny_credit')]
        private readonly ?PokemonCredit $smallShinyCredit,
        #[SerializedName('big_regular_credit')]
        private readonly ?PokemonCredit $bigRegularCredit,
        #[SerializedName('big_shiny_credit')]
        private readonly ?PokemonCredit $bigShinyCredit,
    ) {}

    public function getPokemonSlug(): string
    {
        return $this->pokemonSlug;
    }

    public function getPokemonName(): string
    {
        return $this->pokemonName;
    }

    public function getPokemonFrenchName(): string
    {
        return $this->pokemonFrenchName;
    }

    public function getPokemonIcon(): string
    {
        return $this->pokemonIcon;
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

    public function hasAnyCredit(): bool
    {
        return null !== $this->smallRegularCredit
            || null !== $this->smallShinyCredit
            || null !== $this->bigRegularCredit
            || null !== $this->bigShinyCredit;
    }

    public function getCreditCount(): int
    {
        return count(array_filter([
            $this->smallRegularCredit,
            $this->smallShinyCredit,
            $this->bigRegularCredit,
            $this->bigShinyCredit,
        ]));
    }
}
