<?php

declare(strict_types=1);

namespace App\ResponseObject\ColorGame;

use Symfony\Component\Serializer\Attribute\SerializedName;

final class ColorGameRound
{
    /**
     * @param ColorGameChoice[] $choices
     */
    public function __construct(
        #[SerializedName('position')]
        private readonly int $position,
        #[SerializedName('pokemon')]
        private readonly ColorGamePokemon $pokemon,
        #[SerializedName('choices')]
        private readonly array $choices,
    ) {}

    public function getPosition(): int
    {
        return $this->position;
    }

    public function getPokemon(): ColorGamePokemon
    {
        return $this->pokemon;
    }

    /**
     * @return ColorGameChoice[]
     */
    public function getChoices(): array
    {
        return $this->choices;
    }
}
