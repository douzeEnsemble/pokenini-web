<?php

declare(strict_types=1);

namespace App\ResponseObject\WhichShinyGame;

use Symfony\Component\Serializer\Attribute\SerializedName;

/**
 * Shiny slot (1 or 2) is the answer of the round: it must never reach the browser before the answer.
 */
final class WhichShinyGameRound
{
    public function __construct(
        #[SerializedName('position')]
        private readonly int $position,
        #[SerializedName('pokemon')]
        private readonly WhichShinyGamePokemon $pokemon,
        #[SerializedName('shiny_slot')]
        private readonly int $shinySlot,
    ) {}

    public function getPosition(): int
    {
        return $this->position;
    }

    public function getPokemon(): WhichShinyGamePokemon
    {
        return $this->pokemon;
    }

    public function getShinySlot(): int
    {
        return $this->shinySlot;
    }
}
