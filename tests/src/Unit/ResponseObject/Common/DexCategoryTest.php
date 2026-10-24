<?php

declare(strict_types=1);

namespace App\Tests\Unit\ResponseObject\Common;

use App\ResponseObject\Common\DexCategory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(DexCategory::class)]
final class DexCategoryTest extends TestCase
{
    #[Test]
    public function getters(): void
    {
        $category = new DexCategory('main', 'Main games', 'Jeux principaux', 1);

        $this->assertSame('main', $category->getSlug());
        $this->assertSame('Main games', $category->getName());
        $this->assertSame('Jeux principaux', $category->getFrenchName());
        $this->assertSame(1, $category->getOrderNumber());
    }
}
