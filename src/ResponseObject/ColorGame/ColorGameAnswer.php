<?php

declare(strict_types=1);

namespace App\ResponseObject\ColorGame;

use Symfony\Component\Serializer\Attribute\SerializedName;

final class ColorGameAnswer
{
    public function __construct(
        #[SerializedName('position')]
        private readonly int $position,
        #[SerializedName('is_correct')]
        private readonly bool $isCorrect,
        #[SerializedName('chosen_choice')]
        private readonly int $chosenChoice,
        #[SerializedName('correct_choice')]
        private readonly int $correctChoice,
        #[SerializedName('game')]
        private readonly ColorGame $game,
    ) {}

    public function getPosition(): int
    {
        return $this->position;
    }

    public function isCorrect(): bool
    {
        return $this->isCorrect;
    }

    public function getChosenChoice(): int
    {
        return $this->chosenChoice;
    }

    public function getCorrectChoice(): int
    {
        return $this->correctChoice;
    }

    public function getGame(): ColorGame
    {
        return $this->game;
    }
}
