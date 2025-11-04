<?php

declare(strict_types=1);

namespace App\ResponseObject;

final class Type
{
    public function __construct(
        private readonly string $name,
        private readonly string $frenchName,
        private readonly string $slug,
        private readonly string $color,
    ) {}

    public function getName(): string
    {
        return $this->name;
    }

    public function getFrenchName(): string
    {
        return $this->frenchName;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function getColor(): string
    {
        return $this->color;
    }
}
