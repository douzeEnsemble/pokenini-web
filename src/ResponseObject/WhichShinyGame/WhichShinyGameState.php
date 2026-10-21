<?php

declare(strict_types=1);

namespace App\ResponseObject\WhichShinyGame;

use Symfony\Component\Serializer\Attribute\SerializedName;

final class WhichShinyGameState
{
    public function __construct(
        #[SerializedName('game')]
        private readonly WhichShinyGame $game,
        #[SerializedName('round')]
        private readonly ?WhichShinyGameRound $round,
    ) {}

    public function getGame(): WhichShinyGame
    {
        return $this->game;
    }

    public function getRound(): ?WhichShinyGameRound
    {
        return $this->round;
    }
}
