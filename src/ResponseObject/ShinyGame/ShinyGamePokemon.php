<?php

declare(strict_types=1);

namespace App\ResponseObject\ShinyGame;

use Symfony\Component\Serializer\Attribute\SerializedName;

final class ShinyGamePokemon
{
    public function __construct(
        #[SerializedName('slug')]
        private readonly string $slug,
        #[SerializedName('name')]
        private readonly string $name,
        #[SerializedName('french_name')]
        private readonly string $frenchName,
        #[SerializedName('simplified_name')]
        private readonly string $simplifiedName,
        #[SerializedName('simplified_french_name')]
        private readonly string $simplifiedFrenchName,
        #[SerializedName('forms_label')]
        private readonly string $formsLabel,
        #[SerializedName('forms_french_label')]
        private readonly string $formsFrenchLabel,
        #[SerializedName('national_dex_number')]
        private readonly int $nationalDexNumber,
        #[SerializedName('icon')]
        private readonly string $icon,
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

    /**
     * @return array<string, string> the name indexed by locale
     */
    public function getNames(): array
    {
        return ['en' => $this->getName(), 'fr' => $this->getFrenchName()];
    }

    public function getSimplifiedName(): string
    {
        return $this->simplifiedName;
    }

    public function getSimplifiedFrenchName(): string
    {
        return $this->simplifiedFrenchName;
    }

    public function getFormsLabel(): string
    {
        return $this->formsLabel;
    }

    public function getFormsFrenchLabel(): string
    {
        return $this->formsFrenchLabel;
    }

    /**
     * @return array<string, string> the forms label indexed by locale
     */
    public function getFormsLabels(): array
    {
        return ['en' => $this->getFormsLabel(), 'fr' => $this->getFormsFrenchLabel()];
    }

    public function getNationalDexNumber(): int
    {
        return $this->nationalDexNumber;
    }

    public function getIcon(): string
    {
        return $this->icon;
    }
}
