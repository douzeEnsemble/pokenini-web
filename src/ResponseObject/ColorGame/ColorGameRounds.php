<?php

declare(strict_types=1);

namespace App\ResponseObject\ColorGame;

use Symfony\Component\Serializer\Attribute\SerializedName;

final class ColorGameRounds
{
    /**
     * @param ColorGameAnsweredRound[] $rounds
     */
    public function __construct(
        #[SerializedName('game')]
        private readonly ColorGame $game,
        #[SerializedName('rounds')]
        private readonly array $rounds,
    ) {}

    public function getGame(): ColorGame
    {
        return $this->game;
    }

    /**
     * @return ColorGameAnsweredRound[]
     */
    public function getRounds(): array
    {
        return $this->rounds;
    }
}
