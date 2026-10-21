<?php

declare(strict_types=1);

namespace App\ResponseObject\WhichShinyGame;

use Symfony\Component\Serializer\Attribute\SerializedName;

final class WhichShinyGameAnswer
{
    public function __construct(
        #[SerializedName('position')]
        private readonly int $position,
        #[SerializedName('is_correct')]
        private readonly bool $isCorrect,
        #[SerializedName('chosen_slot')]
        private readonly int $chosenSlot,
        #[SerializedName('shiny_slot')]
        private readonly int $shinySlot,
        #[SerializedName('game')]
        private readonly WhichShinyGame $game,
    ) {}

    public function getPosition(): int
    {
        return $this->position;
    }

    public function isCorrect(): bool
    {
        return $this->isCorrect;
    }

    public function getChosenSlot(): int
    {
        return $this->chosenSlot;
    }

    public function getShinySlot(): int
    {
        return $this->shinySlot;
    }

    public function getGame(): WhichShinyGame
    {
        return $this->game;
    }
}
