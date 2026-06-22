<?php

declare(strict_types=1);

namespace App\ResponseObject\Election;

use App\ResponseObject\Album\Pokedex;
use App\ResponseObject\Common\Pokemon;
use Symfony\Component\Serializer\Attribute\SerializedName;

final class ElectionIndex
{
    /**
     * @param Pokemon[]    $pokemons
     * @param TopPokemon[] $electionTop
     * @param array{
     *      view_count: array{sum: int, max: int},
     *      win_count: array{sum: int, max: int},
     *      completion: array{under_max_count: int, at_max_count: int},
     *      dex_total_count: int,
     *      round_count: int,
     *      winner_average: float,
     *      total_round_count: int,
     * } $metrics
     */
    public function __construct(
        #[SerializedName('type')]
        private readonly string $type,
        #[SerializedName('pokemons')]
        private readonly array $pokemons,
        #[SerializedName('pokedex')]
        private readonly ?Pokedex $pokedex,
        #[SerializedName('election_top')]
        private readonly array $electionTop,
        #[SerializedName('metrics')]
        private readonly array $metrics,
        #[SerializedName('detached_count')]
        private readonly int $detachedCount,
        #[SerializedName('is_the_last_one')]
        private readonly bool $isTheLastOne,
        #[SerializedName('is_the_last_page')]
        private readonly bool $isTheLastPage,
    ) {}

    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @return Pokemon[]
     */
    public function getPokemons(): array
    {
        return $this->pokemons;
    }

    public function getPokedex(): ?Pokedex
    {
        return $this->pokedex;
    }

    /**
     * @return TopPokemon[]
     */
    public function getElectionTop(): array
    {
        return $this->electionTop;
    }

    /**
     * @return array{
     *  view_count: array{sum: int, max: int},
     *  win_count: array{sum: int, max: int},
     *  completion: array{under_max_count: int, at_max_count: int},
     *  dex_total_count: int,
     *  round_count: int,
     *  winner_average: float,
     *  total_round_count: int,
     * }
     */
    public function getMetrics(): array
    {
        return $this->metrics;
    }

    public function getDetachedCount(): int
    {
        return $this->detachedCount;
    }

    public function isTheLastOne(): bool
    {
        return $this->isTheLastOne;
    }

    public function isTheLastPage(): bool
    {
        return $this->isTheLastPage;
    }
}
