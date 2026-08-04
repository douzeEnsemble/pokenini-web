<?php

declare(strict_types=1);

namespace App\ResponseObject\Album;

use Symfony\Component\Serializer\Attribute\SerializedName;

final class TrainerDexLink
{
    public function __construct(
        #[SerializedName('id')]
        private readonly string $id,
        #[SerializedName('direction')]
        private readonly string $direction,
        #[SerializedName('target_dex_slug')]
        private readonly string $targetDexSlug,
        #[SerializedName('target_name')]
        private readonly string $targetName,
        #[SerializedName('target_french_name')]
        private readonly string $targetFrenchName,
    ) {}

    public function getId(): string
    {
        return $this->id;
    }

    public function getDirection(): string
    {
        return $this->direction;
    }

    public function getTargetDexSlug(): string
    {
        return $this->targetDexSlug;
    }

    public function getTargetName(): string
    {
        return $this->targetName;
    }

    public function getTargetFrenchName(): string
    {
        return $this->targetFrenchName;
    }
}
