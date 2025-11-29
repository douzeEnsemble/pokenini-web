<?php

declare(strict_types=1);

namespace App\DTO;

use App\ResponseObject\Election\TopPokemon;

final class ElectionTop
{
    /**
     * @param TopPokemon[] $items
     */
    public function __construct(
        private readonly array $items
    ) {}

    /**
     * @return TopPokemon[]
     */
    public function getItems(): array
    {
        return $this->items;
    }
}
