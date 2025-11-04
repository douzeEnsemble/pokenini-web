<?php

declare(strict_types=1);

namespace App\ResponseObject;

final class GameBundle
{
    public function __construct(
        private readonly string $name,
        private readonly string $frenchName,
        private readonly string $slug,
        private readonly string $generationSlug,
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

    public function getGenerationSlug(): string
    {
        return $this->generationSlug;
    }
}
