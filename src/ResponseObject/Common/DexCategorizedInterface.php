<?php

declare(strict_types=1);

namespace App\ResponseObject\Common;

/**
 * An item of a dex list, which can be grouped by the category of its dex.
 */
interface DexCategorizedInterface
{
    public function getCategory(): ?DexCategory;
}
