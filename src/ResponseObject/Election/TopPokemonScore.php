<?php

declare(strict_types=1);

namespace App\ResponseObject\Election;

use Symfony\Component\Serializer\Attribute\SerializedName;

final class TopPokemonScore
{
    public function __construct(
        #[SerializedName('elo')]
        private readonly float $elo,
        #[SerializedName('significance')]
        private readonly bool $significance,
    ) {}

    public function getElo(): float
    {
        return $this->elo;
    }

    public function isSignificance(): bool
    {
        return $this->significance;
    }
}
