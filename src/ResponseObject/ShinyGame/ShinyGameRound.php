<?php

declare(strict_types=1);

namespace App\ResponseObject\ShinyGame;

use Symfony\Component\Serializer\Attribute\SerializedName;

/**
 * Is shiny is the answer of the round: it must never reach the browser before the answer.
 */
final class ShinyGameRound
{
    public function __construct(
        #[SerializedName('position')]
        private readonly int $position,
        #[SerializedName('pokemon')]
        private readonly ShinyGamePokemon $pokemon,
        #[SerializedName('is_shiny')]
        private readonly bool $isShiny,
    ) {}

    public function getPosition(): int
    {
        return $this->position;
    }

    public function getPokemon(): ShinyGamePokemon
    {
        return $this->pokemon;
    }

    public function isShiny(): bool
    {
        return $this->isShiny;
    }
}
