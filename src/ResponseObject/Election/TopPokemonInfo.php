<?php

declare(strict_types=1);

namespace App\ResponseObject\Election;

use Symfony\Component\Serializer\Attribute\SerializedName;

final class TopPokemonInfo
{
    public function __construct(
        #[SerializedName('slug')]
        private readonly string $slug,
        #[SerializedName('labels')]
        private readonly TopPokemonLabels $labels,
        #[SerializedName('national_dex_number')]
        private readonly int $nationalDexNumber,
        #[SerializedName('pokemon_icon')]
        private readonly string $icon,
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

    public function getIcon(): string
    {
        return $this->icon;
    }
}
