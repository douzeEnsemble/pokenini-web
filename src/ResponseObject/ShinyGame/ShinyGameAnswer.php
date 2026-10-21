<?php

declare(strict_types=1);

namespace App\ResponseObject\ShinyGame;

use Symfony\Component\Serializer\Attribute\SerializedName;

final class ShinyGameAnswer
{
    public function __construct(
        #[SerializedName('position')]
        private readonly int $position,
        #[SerializedName('is_correct')]
        private readonly bool $isCorrect,
        #[SerializedName('chosen_shiny')]
        private readonly bool $chosenShiny,
        #[SerializedName('is_shiny')]
        private readonly bool $isShiny,
        #[SerializedName('game')]
        private readonly ShinyGame $game,
    ) {}

    public function getPosition(): int
    {
        return $this->position;
    }

    public function isCorrect(): bool
    {
        return $this->isCorrect;
    }

    public function isChosenShiny(): bool
    {
        return $this->chosenShiny;
    }

    public function isShiny(): bool
    {
        return $this->isShiny;
    }

    public function getGame(): ShinyGame
    {
        return $this->game;
    }
}
