<?php

declare(strict_types=1);

namespace App\ResponseObject\Common;

use Symfony\Component\Serializer\Attribute\SerializedName;

final class DexCategory
{
    public function __construct(
        #[SerializedName('slug')]
        private readonly string $slug,
        #[SerializedName('name')]
        private readonly string $name,
        #[SerializedName('french_name')]
        private readonly string $frenchName,
        #[SerializedName('order_number')]
        private readonly int $orderNumber,
    ) {}

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getFrenchName(): string
    {
        return $this->frenchName;
    }

    public function getOrderNumber(): int
    {
        return $this->orderNumber;
    }
}
