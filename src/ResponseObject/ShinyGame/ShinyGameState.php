<?php

declare(strict_types=1);

namespace App\ResponseObject\ShinyGame;

use Symfony\Component\Serializer\Attribute\SerializedName;

final class ShinyGameState
{
    public function __construct(
        #[SerializedName('game')]
        private readonly ShinyGame $game,
        #[SerializedName('round')]
        private readonly ?ShinyGameRound $round,
    ) {}

    public function getGame(): ShinyGame
    {
        return $this->game;
    }

    public function getRound(): ?ShinyGameRound
    {
        return $this->round;
    }
}
