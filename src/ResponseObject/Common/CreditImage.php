<?php

declare(strict_types=1);

namespace App\ResponseObject\Common;

use Symfony\Component\Serializer\Attribute\SerializedName;

final class CreditImage
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
        private readonly string $size,
        #[SerializedName('is_shiny')]
        private readonly bool $isShiny,
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

    public function getSize(): string
    {
        return $this->size;
    }

    public function isShiny(): bool
    {
        return $this->isShiny;
    }
}
