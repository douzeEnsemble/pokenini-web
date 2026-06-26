<?php

declare(strict_types=1);

namespace App\ResponseObject\Album;

use Symfony\Component\Serializer\Attribute\SerializedName;

final class DexFlags
{
    public function __construct(
        #[SerializedName('is_shiny')]
        private readonly bool $isShiny,
        #[SerializedName('is_private')]
        private readonly bool $isPrivate,
        #[SerializedName('is_on_home')]
        private readonly bool $isOnHome,
        #[SerializedName('is_display_form')]
        private readonly bool $isDisplayForm,
        #[SerializedName('is_released')]
        private readonly bool $isReleased,
        #[SerializedName('is_premium')]
        private readonly bool $isPremium,
        #[SerializedName('is_custom')]
        private readonly bool $isCustom,
    ) {}

    public function isShiny(): bool
    {
        return $this->isShiny;
    }

    public function isPrivate(): bool
    {
        return $this->isPrivate;
    }

    public function isOnHome(): bool
    {
        return $this->isOnHome;
    }

    public function isDisplayForm(): bool
    {
        return $this->isDisplayForm;
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
