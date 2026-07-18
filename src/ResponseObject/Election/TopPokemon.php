<?php

declare(strict_types=1);

namespace App\ResponseObject\Election;

use App\ResponseObject\Common\PokemonCredit;
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

    public function getPokemonIcon(): string
    {
        return $this->pokemon->getIcon();
    }

    public function getPokemonName(): string
    {
        return $this->pokemon->getLabels()->getName();
    }

    public function getPokemonFrenchName(): string
    {
        return $this->pokemon->getLabels()->getFrenchName();
    }

    public function getPokemonSmallRegularCredit(): ?PokemonCredit
    {
        return $this->pokemon->getSmallRegularCredit();
    }

    public function getPokemonSmallShinyCredit(): ?PokemonCredit
    {
        return $this->pokemon->getSmallShinyCredit();
    }

    public function getPokemonBigRegularCredit(): ?PokemonCredit
    {
        return $this->pokemon->getBigRegularCredit();
    }

    public function getPokemonBigShinyCredit(): ?PokemonCredit
    {
        return $this->pokemon->getBigShinyCredit();
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
