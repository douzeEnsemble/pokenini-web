<?php

declare(strict_types=1);

namespace App\ResponseObject;

final class Collection
{
    public function __construct(
        private readonly string $name,
        private readonly string $frenchName,
        private readonly string $slug,
        private readonly int $orderNumber,
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

    public function getOrderNumber(): int
    {
        return $this->orderNumber;
    }
}
