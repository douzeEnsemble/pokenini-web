<?php

declare(strict_types=1);

namespace App\ResponseObject\Election;

use Symfony\Component\Serializer\Attribute\SerializedName;

final class TopPokemonLabels
{
    public function __construct(
        #[SerializedName('name')]
        private readonly string $name,
        #[SerializedName('french_name')]
        private readonly string $frenchName,
        #[SerializedName('simplified_name')]
        private readonly string $simplifiedName,
        #[SerializedName('simplified_french_name')]
        private readonly string $simplifiedFrenchName,
    ) {}

    public function getName(): string
    {
        return $this->name;
    }

    public function getFrenchName(): string
    {
        return $this->frenchName;
    }

    public function getSimplifiedName(): string
    {
        return $this->simplifiedName;
    }

    public function getSimplifiedFrenchName(): string
    {
        return $this->simplifiedFrenchName;
    }
}
