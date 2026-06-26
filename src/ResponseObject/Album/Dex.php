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
        #[SerializedName('flags')]
        private readonly DexFlags $flags,
        #[SerializedName('display_template')]
        private readonly ?string $displayTemplate,
        #[SerializedName('region')]
        private readonly ?DexRegion $region,
        #[SerializedName('selection_rule')]
        private readonly string $selectionRule,
        #[SerializedName('description')]
        private readonly string $description,
        #[SerializedName('french_description')]
        private readonly string $frenchDescription,
        #[SerializedName('version')]
        private readonly string $version,
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

    public function getFlags(): DexFlags
    {
        return $this->flags;
    }

    public function getDisplayTemplate(): ?string
    {
        return $this->displayTemplate;
    }

    public function getRegion(): ?DexRegion
    {
        return $this->region;
    }

    public function getSelectionRule(): string
    {
        return $this->selectionRule;
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

    // ── Delegation methods — keep public API unchanged for Twig templates ──

    public function isShiny(): bool
    {
        return $this->flags->isShiny();
    }

    public function isPrivate(): bool
    {
        return $this->flags->isPrivate();
    }

    public function isOnHome(): bool
    {
        return $this->flags->isOnHome();
    }

    public function isDisplayForm(): bool
    {
        return $this->flags->isDisplayForm();
    }

    public function isReleased(): bool
    {
        return $this->flags->isReleased();
    }

    public function isPremium(): bool
    {
        return $this->flags->isPremium();
    }

    public function isCustom(): bool
    {
        return $this->flags->isCustom();
    }

    public function getRegionName(): ?string
    {
        return $this->region?->getName();
    }

    public function getRegionFrenchName(): ?string
    {
        return $this->region?->getFrenchName();
    }
}
