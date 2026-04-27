<?php

declare(strict_types=1);

namespace App\ResponseObject\Album;

use Symfony\Component\Serializer\Attribute\SerializedName;

/**
 * @SuppressWarnings("PHPMD.ExcessiveParameterList")
 */
final class Dex
{
    public function __construct(
        #[SerializedName('slug')]
        private readonly string $slug,
        #[SerializedName('original_slug')]
        private readonly string $originalSlug,
        #[SerializedName('name')]
        private readonly string $name,
        #[SerializedName('french_name')]
        private readonly string $frenchName,
        #[SerializedName('is_shiny')]
        private readonly bool $isShiny,
        #[SerializedName('is_private')]
        private readonly bool $isPrivate,
        #[SerializedName('is_display_form')]
        private readonly bool $isDisplayForm,
        #[SerializedName('display_template')]
        private readonly ?string $displayTemplate,
        #[SerializedName('region_name')]
        private readonly ?string $regionName,
        #[SerializedName('region_french_name')]
        private readonly ?string $regionFrenchName,
        #[SerializedName('description')]
        private readonly string $description,
        #[SerializedName('french_description')]
        private readonly string $frenchDescription,
        #[SerializedName('version')]
        private readonly string $version,
        #[SerializedName('is_released')]
        private readonly bool $isReleased,
        #[SerializedName('is_premium')]
        private readonly bool $isPremium,
        #[SerializedName('is_custom')]
        private readonly bool $isCustom,
    ) {}

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function getOriginalSlug(): string
    {
        return $this->originalSlug;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getFrenchName(): string
    {
        return $this->frenchName;
    }

    public function isShiny(): bool
    {
        return $this->isShiny;
    }

    public function isPrivate(): bool
    {
        return $this->isPrivate;
    }

    public function isDisplayForm(): bool
    {
        return $this->isDisplayForm;
    }

    public function getDisplayTemplate(): ?string
    {
        return $this->displayTemplate;
    }

    public function getRegionName(): ?string
    {
        return $this->regionName;
    }

    public function getRegionFrenchName(): ?string
    {
        return $this->regionFrenchName;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getFrenchDescription(): string
    {
        return $this->frenchDescription;
    }

    public function getVersion(): string
    {
        return $this->version;
    }

    public function isReleased(): bool
    {
        return $this->isReleased;
    }

    public function isPremium(): bool
    {
        return $this->isPremium;
    }

    public function isCustom(): bool
    {
        return $this->isCustom;
    }
}
