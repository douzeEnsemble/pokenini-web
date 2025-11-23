<?php

declare(strict_types=1);

namespace App\ResponseObject\Label;

use Symfony\Component\Serializer\Attribute\SerializedName;

final class Collection
{
    public function __construct(
        #[SerializedName('name')]
        private readonly string $name,
        #[SerializedName('french_name')]
        private readonly string $frenchName,
        #[SerializedName('slug')]
        private readonly string $slug,
        #[SerializedName('order_number')]
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
