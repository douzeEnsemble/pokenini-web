<?php

declare(strict_types=1);

namespace App\ResponseObject\ColorGame;

use Symfony\Component\Serializer\Attribute\SerializedName;

final class ColorGameAnsweredRound
{
    /**
     * @param ColorGameChoice[] $choices
     */
    public function __construct(
        #[SerializedName('position')]
        private readonly int $position,
        #[SerializedName('pokemon')]
        private readonly ColorGamePokemon $pokemon,
        #[SerializedName('is_correct')]
        private readonly bool $isCorrect,
        #[SerializedName('choices')]
        private readonly array $choices,
        #[SerializedName('chosen_choice')]
        private readonly int $chosenChoice,
        #[SerializedName('correct_choice')]
        private readonly int $correctChoice,
    ) {}

    public function getPosition(): int
    {
        return $this->position;
    }

    public function getPokemon(): ColorGamePokemon
    {
        return $this->pokemon;
    }

    public function isCorrect(): bool
    {
        return $this->isCorrect;
    }

    /**
     * @return ColorGameChoice[]
     */
    public function getChoices(): array
    {
        return $this->choices;
    }

    public function getChosenChoice(): int
    {
        return $this->chosenChoice;
    }

    public function getCorrectChoice(): int
    {
        return $this->correctChoice;
    }
}
