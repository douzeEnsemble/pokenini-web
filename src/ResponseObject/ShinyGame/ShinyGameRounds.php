<?php

declare(strict_types=1);

namespace App\ResponseObject\ShinyGame;

use Symfony\Component\Serializer\Attribute\SerializedName;

final class ShinyGameRounds
{
    /**
     * @param ShinyGameAnsweredRound[] $rounds
     */
    public function __construct(
        #[SerializedName('game')]
        private readonly ShinyGame $game,
        #[SerializedName('rounds')]
        private readonly array $rounds,
    ) {}

    public function getGame(): ShinyGame
    {
        return $this->game;
    }

    /**
     * @return ShinyGameAnsweredRound[]
     */
    public function getRounds(): array
    {
        return $this->rounds;
    }
}
