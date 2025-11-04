<?php

declare(strict_types=1);

namespace App\ResponseObject;

abstract class AbstractForm
{
    public function __construct(
        private readonly string $name,
        private readonly string $frenchName,
        private readonly string $slug,
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
}
