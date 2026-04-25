<?php

declare(strict_types=1);

namespace App\ResponseObject\Album;

use Symfony\Component\Serializer\Attribute\SerializedName;

final class Album
{
    /**
     * @param array<string, array<int, string>|string> $filters
     */
    public function __construct(
        #[SerializedName('pokedex')]
        private readonly Pokedex $pokedex,
        #[SerializedName('filters')]
        private readonly array $filters,
    ) {}

    public function getPokedex(): Pokedex
    {
        return $this->pokedex;
    }

    /**
     * @return array<string, array<int, string>|string>
     */
    public function getFilters(): array
    {
        return $this->filters;
    }
}
