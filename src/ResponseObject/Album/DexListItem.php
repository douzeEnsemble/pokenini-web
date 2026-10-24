<?php

declare(strict_types=1);

namespace App\ResponseObject\Album;

use App\ResponseObject\Common\DexCategorizedInterface;
use App\ResponseObject\Common\DexCategory;
use Symfony\Component\Serializer\Attribute\SerializedName;

final class DexListItem implements DexCategorizedInterface
{
    public function __construct(
        #[SerializedName('dex')]
        private readonly DexListItemRef $dex,
        #[SerializedName('settings')]
        private readonly DexListItemSettings $settings,
        #[SerializedName('flags')]
        private readonly DexFlags $flags,
        #[SerializedName('report')]
        private readonly ?Report $report = null,
        #[SerializedName('category')]
        private readonly ?DexCategory $category = null,
    ) {}

    public function getDex(): DexListItemRef
    {
        return $this->dex;
    }

    public function getSettings(): DexListItemSettings
    {
        return $this->settings;
    }

    public function getFlags(): DexFlags
    {
        return $this->flags;
    }

    public function getReport(): ?Report
    {
        return $this->report;
    }

    #[\Override]
    public function getCategory(): ?DexCategory
    {
        return $this->category;
    }
}
