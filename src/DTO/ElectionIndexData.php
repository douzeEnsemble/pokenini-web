<?php

declare(strict_types=1);

namespace App\DTO;

use App\ResponseObject\Album\Pokedex;
use App\ResponseObject\Common\Pokemon;

final class ElectionIndexData
{
    /**
     * @param Pokemon[] $pokemons
     */
    public function __construct(
        public readonly string $listType,
        public readonly array $pokemons,
        public readonly ?Pokedex $pokedex,
        public readonly ElectionTop $electionTop,
        public readonly ElectionMetrics $metrics,
        public readonly int $detachedCount,
        public readonly bool $isTheLastOne,
        public readonly bool $isTheLastPage,
    ) {}
}
