<?php

declare(strict_types=1);

namespace App\Twig;

use App\Service\DexCategoryGroupService;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

/**
 * Group the dex of a list by category.
 */
final class DexCategoryExtension extends AbstractExtension
{
    public function __construct(private readonly DexCategoryGroupService $service) {}

    #[\Override]
    public function getFilters(): array
    {
        return [
            new TwigFilter('dex_category_groups', [$this->service, 'group']),
        ];
    }
}
