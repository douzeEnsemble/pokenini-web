<?php

declare(strict_types=1);

namespace App\ResponseObject\Common;

use Symfony\Component\Serializer\Attribute\SerializedName;

final class GameBundlesGroup
{
    /**
     * @param PokemonSlugRef[] $normal
     * @param PokemonSlugRef[] $shiny
     */
    public function __construct(
        #[SerializedName('normal')]
        private readonly array $normal,
        #[SerializedName('shiny')]
        private readonly array $shiny,
    ) {}

    /**
     * @return PokemonSlugRef[]
     */
    public function getNormal(): array
    {
        return $this->normal;
    }

    /**
     * @return PokemonSlugRef[]
     */
    public function getShiny(): array
    {
        return $this->shiny;
    }
}
