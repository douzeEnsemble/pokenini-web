<?php

declare(strict_types=1);

namespace App\ResponseObject\Election;

use App\ResponseObject\Album\DexFlags;
use Symfony\Component\Serializer\Attribute\SerializedName;

final class ElectionDexListItem
{
    public function __construct(
        #[SerializedName('slug')]
        private readonly string $slug,
        #[SerializedName('name')]
        private readonly string $name,
        #[SerializedName('french_name')]
        private readonly string $frenchName,
        #[SerializedName('flags')]
        private readonly DexFlags $flags,
        #[SerializedName('display_template')]
        private readonly ?string $displayTemplate,
        #[SerializedName('description')]
        private readonly ?string $description,
        #[SerializedName('french_description')]
        private readonly ?string $frenchDescription,
        #[SerializedName('dex_total_count')]
        private readonly ?int $dexTotalCount,
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

    public function getFlags(): DexFlags
    {
        return $this->flags;
    }

    public function getDisplayTemplate(): ?string
    {
        return $this->displayTemplate;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getFrenchDescription(): ?string
    {
        return $this->frenchDescription;
    }

    public function getDexTotalCount(): ?int
    {
        return $this->dexTotalCount;
    }
}
