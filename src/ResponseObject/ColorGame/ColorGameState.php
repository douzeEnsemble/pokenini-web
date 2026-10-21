<?php

declare(strict_types=1);

namespace App\ResponseObject\ColorGame;

use Symfony\Component\Serializer\Attribute\SerializedName;

final class ColorGameState
{
    public function __construct(
        #[SerializedName('game')]
        private readonly ColorGame $game,
        #[SerializedName('round')]
        private readonly ?ColorGameRound $round,
    ) {}

    public function getGame(): ColorGame
    {
        return $this->game;
    }

    public function getRound(): ?ColorGameRound
    {
        return $this->round;
    }
}
