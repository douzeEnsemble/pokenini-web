<?php

declare(strict_types=1);

namespace App\ResponseObject\Election;

use Symfony\Component\Serializer\Attribute\SerializedName;

final class TopPokemon
{
    public function __construct(
        #[SerializedName('pokemon')]
        private readonly TopPokemonInfo $pokemon,
        #[SerializedName('score')]
        private readonly TopPokemonScore $score,
    ) {}

    public function getPokemonSlug(): string
    {
        return $this->pokemon->getSlug();
    }

    public function getPokemonName(): string
    {
        return $this->pokemon->getLabels()->getName();
    }

    public function getPokemonNationalDexNumber(): int
    {
        return $this->pokemon->getNationalDexNumber();
    }

    public function getPokemonSimplifiedName(): string
    {
        return $this->pokemon->getLabels()->getName();
    }

    public function getPokemonFrenchName(): string
    {
        return $this->pokemon->getLabels()->getFrenchName();
    }

    public function getPokemonSimplifiedFrenchName(): string
    {
        return $this->pokemon->getLabels()->getFrenchName();
    }

    public function getPokemonIcon(): string
    {
        return $this->pokemon->getSlug();
    }

    public function getElo(): float
    {
        return $this->score->getElo();
    }

    public function isSignificance(): bool
    {
        return $this->score->isSignificance();
    }
}
