<?php

declare(strict_types=1);

namespace App\ResponseObject\ShinyGame;

use Symfony\Component\Serializer\Attribute\SerializedName;

final class ShinyGameAnsweredRound
{
    public function __construct(
        #[SerializedName('position')]
        private readonly int $position,
        #[SerializedName('pokemon')]
        private readonly ShinyGamePokemon $pokemon,
        #[SerializedName('is_correct')]
        private readonly bool $isCorrect,
        #[SerializedName('is_shiny')]
        private readonly bool $isShiny,
        #[SerializedName('chosen_shiny')]
        private readonly bool $chosenShiny,
    ) {}

    public function getPosition(): int
    {
        return $this->position;
    }

    public function getPokemon(): ShinyGamePokemon
    {
        return $this->pokemon;
    }

    public function isCorrect(): bool
    {
        return $this->isCorrect;
    }

    public function isShiny(): bool
    {
        return $this->isShiny;
    }

    public function isChosenShiny(): bool
    {
        return $this->chosenShiny;
    }
}
