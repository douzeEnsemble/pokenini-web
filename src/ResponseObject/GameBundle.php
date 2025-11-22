<?php

declare(strict_types=1);

namespace App\ResponseObject;

use Symfony\Component\Serializer\Annotation\SerializedName;

final class GameBundle
{
    public function __construct(
        #[SerializedName('name')]
        private readonly string $name,
        #[SerializedName('french_name')]
        private readonly string $frenchName,
        #[SerializedName('slug')]
        private readonly string $slug,
        #[SerializedName('generation_slug')]
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
