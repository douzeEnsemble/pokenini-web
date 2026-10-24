<?php

declare(strict_types=1);

namespace App\ResponseObject\ColorGame;

use App\ResponseObject\Common\DexCategorizedInterface;
use App\ResponseObject\Common\DexCategory;
use Symfony\Component\Serializer\Attribute\SerializedName;

/**
 * @SuppressWarnings("PHPMD.ExcessiveParameterList")
 */
final class ColorGameDexListItem implements DexCategorizedInterface
{
    public function __construct(
        #[SerializedName('slug')]
        private readonly string $slug,
        #[SerializedName('name')]
        private readonly string $name,
        #[SerializedName('french_name')]
        private readonly string $frenchName,
        #[SerializedName('is_shiny')]
        private readonly bool $isShiny,
        #[SerializedName('is_released')]
        private readonly bool $isReleased,
        #[SerializedName('is_premium')]
        private readonly bool $isPremium,
        #[SerializedName('description')]
        private readonly ?string $description,
        #[SerializedName('french_description')]
        private readonly ?string $frenchDescription,
        #[SerializedName('playable_count')]
        private readonly int $playableCount,
        #[SerializedName('current_game')]
        private readonly ?ColorGame $currentGame,
        #[SerializedName('category')]
        private readonly ?DexCategory $category = null,
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

    public function isShiny(): bool
    {
        return $this->isShiny;
    }

    public function isReleased(): bool
    {
        return $this->isReleased;
    }

    public function isPremium(): bool
    {
        return $this->isPremium;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getFrenchDescription(): ?string
    {
        return $this->frenchDescription;
    }

    public function getPlayableCount(): int
    {
        return $this->playableCount;
    }

    public function getCurrentGame(): ?ColorGame
    {
        return $this->currentGame;
    }

    #[\Override]
    public function getCategory(): ?DexCategory
    {
        return $this->category;
    }
}
