<?php

declare(strict_types=1);

namespace App\ResponseObject\Common;

use Symfony\Component\Serializer\Attribute\SerializedName;

final class PokemonLabels
{
    public function __construct(
        #[SerializedName('name')]
        private readonly string $name,
        #[SerializedName('french_name')]
        private readonly string $frenchName,
        #[SerializedName('simplified_name')]
        private readonly ?string $simplifiedName,
        #[SerializedName('simplified_french_name')]
        private readonly ?string $simplifiedFrenchName,
        #[SerializedName('forms_label')]
        private readonly ?string $formsLabel,
        #[SerializedName('forms_french_label')]
        private readonly ?string $formsFrenchLabel,
    ) {}

    public function getName(): string
    {
        return $this->name;
    }

    public function getFrenchName(): string
    {
        return $this->frenchName;
    }

    public function getSimplifiedName(): ?string
    {
        return $this->simplifiedName;
    }

    public function getSimplifiedFrenchName(): ?string
    {
        return $this->simplifiedFrenchName;
    }

    public function getFormsLabel(): ?string
    {
        return $this->formsLabel;
    }

    public function getFormsFrenchLabel(): ?string
    {
        return $this->formsFrenchLabel;
    }
}
