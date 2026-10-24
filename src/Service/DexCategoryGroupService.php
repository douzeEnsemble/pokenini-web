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
            /**
             * @param array{category: DexCategory, items: list<DexCategorizedInterface>} $first
             * @param array{category: DexCategory, items: list<DexCategorizedInterface>} $second
             */
            static fn (array $first, array $second): int => self::compare($first['category'], $second['category']),
        );

        if ([] !== $uncategorized) {
            $groups[] = ['category' => null, 'items' => $uncategorized];
        }

        return $groups;
    }

    private static function compare(DexCategory $first, DexCategory $second): int
    {
        return [$first->getOrderNumber(), $first->getSlug()] <=> [$second->getOrderNumber(), $second->getSlug()];
    }
}
