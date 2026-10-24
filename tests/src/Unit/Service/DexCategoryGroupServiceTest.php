<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\ResponseObject\Common\DexCategorizedInterface;
use App\ResponseObject\Common\DexCategory;
use App\Service\DexCategoryGroupService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(DexCategoryGroupService::class)]
final class DexCategoryGroupServiceTest extends TestCase
{
    #[Test]
    public function groupInTheOrderOfTheCategories(): void
    {
        $main = new DexCategory('main', 'Main games', 'Jeux principaux', 1);
        $home = new DexCategory('home', 'Home', 'Home', 2);

        $home1 = $this->getItem($home);
        $main1 = $this->getItem($main);
        $other1 = $this->getItem(null);
        $home2 = $this->getItem($home);
        $main2 = $this->getItem(new DexCategory('main', 'Main games', 'Jeux principaux', 1));
        $other2 = $this->getItem(null);

        $groups = new DexCategoryGroupService()->group([$home1, $main1, $other1, $home2, $main2, $other2]);

        $this->assertSame(
            [
                ['category' => $main, 'items' => [$main1, $main2]],
                ['category' => $home, 'items' => [$home1, $home2]],
                ['category' => null, 'items' => [$other1, $other2]],
            ],
            $groups,
        );
    }

    #[Test]
    public function groupSameOrderBySlug(): void
    {
        $beta = new DexCategory('beta', 'Beta', 'Bêta', 1);
        $alpha = new DexCategory('alpha', 'Alpha', 'Alpha', 1);
        $first = new DexCategory('first', 'First', 'Premier', 0);

        $betaItem = $this->getItem($beta);
        $alphaItem = $this->getItem($alpha);
        $firstItem = $this->getItem($first);

        $groups = new DexCategoryGroupService()->group([$betaItem, $alphaItem, $firstItem]);

        $this->assertSame(
            [
                ['category' => $first, 'items' => [$firstItem]],
                ['category' => $alpha, 'items' => [$alphaItem]],
                ['category' => $beta, 'items' => [$betaItem]],
            ],
            $groups,
        );
    }

    #[Test]
    public function groupWithoutCategory(): void
    {
        $item1 = $this->getItem(null);
        $item2 = $this->getItem(null);

        $groups = new DexCategoryGroupService()->group(new \ArrayIterator([3 => $item1, 7 => $item2]));

        $this->assertSame([['category' => null, 'items' => [$item1, $item2]]], $groups);
    }

    #[Test]
    public function groupNothing(): void
    {
        $this->assertSame([], new DexCategoryGroupService()->group([]));
    }

    private function getItem(?DexCategory $category): DexCategorizedInterface
    {
        return new readonly class($category) implements DexCategorizedInterface {
            public function __construct(private ?DexCategory $category) {}

            #[\Override]
            public function getCategory(): ?DexCategory
            {
                return $this->category;
            }
        };
    }
}
