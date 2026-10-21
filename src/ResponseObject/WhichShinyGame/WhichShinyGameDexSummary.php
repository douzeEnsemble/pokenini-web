<?php

declare(strict_types=1);

namespace App\ResponseObject\WhichShinyGame;

use Symfony\Component\Serializer\Attribute\SerializedName;

final class WhichShinyGameDexSummary
{
    public function __construct(
        #[SerializedName('slug')]
        private readonly string $slug,
        #[SerializedName('name')]
        private readonly string $name,
        #[SerializedName('french_name')]
        private readonly string $frenchName,
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
}
