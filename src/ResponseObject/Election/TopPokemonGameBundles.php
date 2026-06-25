<?php

declare(strict_types=1);

namespace App\ResponseObject\Election;

use Symfony\Component\Serializer\Attribute\SerializedName;

final class TopPokemonGameBundles
{
    /**
     * @param TopPokemonSlugRef[] $normal
     * @param TopPokemonSlugRef[] $shiny
     */
    public function __construct(
        #[SerializedName('normal')]
        private readonly array $normal,
        #[SerializedName('shiny')]
        private readonly array $shiny,
    ) {}

    /**
     * @return TopPokemonSlugRef[]
     */
    public function getNormal(): array
    {
        return $this->normal;
    }

    /**
     * @return TopPokemonSlugRef[]
     */
    public function getShiny(): array
    {
        return $this->shiny;
    }
}
