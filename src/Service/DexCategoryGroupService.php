<?php

declare(strict_types=1);

namespace App\Service;

use App\ResponseObject\Common\DexCategorizedInterface;
use App\ResponseObject\Common\DexCategory;

class DexCategoryGroupService
{
    /**
     * Group the items of a dex list by the category of their dex, in the order of the categories.
     * The items keep their order in each group and the dex without category are put in a last group.
     *
     * @param iterable<DexCategorizedInterface> $items
     *
     * @return list<array{category: ?DexCategory, items: list<DexCategorizedInterface>}>
     */
    public function group(iterable $items): array
    {
        /** @var array<string, array{category: DexCategory, items: list<DexCategorizedInterface>}> $groups */
        $groups = [];
        $uncategorized = [];

        foreach ($items as $item) {
            $category = $item->getCategory();

            if (null === $category) {
                $uncategorized[] = $item;

                continue;
            }

            $groups[$category->getSlug()] ??= ['category' => $category, 'items' => []];
            $groups[$category->getSlug()]['items'][] = $item;
        }

        usort(
            $groups,
            static fn (array $first, array $second): int => [$first['category']->getOrderNumber(), $first['category']->getSlug()]
                <=> [$second['category']->getOrderNumber(), $second['category']->getSlug()],
        );

        if ([] !== $uncategorized) {
            $groups[] = ['category' => null, 'items' => $uncategorized];
        }

        return $groups;
    }
}
