<?php

declare(strict_types=1);

namespace App\ResponseObject\Election;

use Symfony\Component\Serializer\Attribute\SerializedName;

final class TopPokemon
{
    public function __construct(
        #[SerializedName('pokemon')]
        private readonly TopPokemonInfo $pokemon,
        #[SerializedName('forms')]
        private readonly ?TopPokemonForms $forms,
        #[SerializedName('types')]
        private readonly TopPokemonTypes $types,
        #[SerializedName('score')]
        private readonly TopPokemonScore $score,
    ) {}

    public function getPokemon(): TopPokemonInfo
    {
        return $this->pokemon;
    }

    public function getForms(): ?TopPokemonForms
    {
        return $this->forms;
    }

    public function getTypes(): TopPokemonTypes
    {
        return $this->types;
    }

    public function getScore(): TopPokemonScore
    {
        return $this->score;
    }
}
