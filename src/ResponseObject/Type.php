<?php

declare(strict_types=1);

namespace App\ResponseObject;

use Symfony\Component\Serializer\Attribute\SerializedName;

final class Type
{
    public function __construct(
        #[SerializedName('name')]
        private readonly string $name,
        #[SerializedName('french_name')]
        private readonly string $frenchName,
        #[SerializedName('slug')]
        private readonly string $slug,
        #[SerializedName('color')]
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
