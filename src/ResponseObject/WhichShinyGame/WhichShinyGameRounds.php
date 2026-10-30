<?php

declare(strict_types=1);

namespace App\ResponseObject\WhichShinyGame;

use Symfony\Component\Serializer\Attribute\SerializedName;

final class WhichShinyGameRounds
{
    /**
     * @param WhichShinyGameAnsweredRound[] $rounds
     */
    public function __construct(
        #[SerializedName('game')]
        private readonly WhichShinyGame $game,
        #[SerializedName('rounds')]
        private readonly array $rounds,
    ) {}

    public function getGame(): WhichShinyGame
    {
        return $this->game;
    }

    /**
     * @return WhichShinyGameAnsweredRound[]
     */
    public function getRounds(): array
    {
        return $this->rounds;
    }
}
