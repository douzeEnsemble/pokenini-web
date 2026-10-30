<?php

declare(strict_types=1);

namespace App\ResponseObject\WhichShinyGame;

use Symfony\Component\Serializer\Attribute\SerializedName;

final class WhichShinyGameAnsweredRound
{
    public function __construct(
        #[SerializedName('position')]
        private readonly int $position,
        #[SerializedName('pokemon')]
        private readonly WhichShinyGamePokemon $pokemon,
        #[SerializedName('is_correct')]
        private readonly bool $isCorrect,
        #[SerializedName('shiny_slot')]
        private readonly int $shinySlot,
        #[SerializedName('chosen_slot')]
        private readonly int $chosenSlot,
    ) {}

    public function getPosition(): int
    {
        return $this->position;
    }

    public function getPokemon(): WhichShinyGamePokemon
    {
        return $this->pokemon;
    }

    public function isCorrect(): bool
    {
        return $this->isCorrect;
    }

    public function getShinySlot(): int
    {
        return $this->shinySlot;
    }

    public function getChosenSlot(): int
    {
        return $this->chosenSlot;
    }
}
