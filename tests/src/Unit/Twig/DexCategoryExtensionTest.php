<?php

declare(strict_types=1);

namespace App\Tests\Unit\Twig;

use App\Service\DexCategoryGroupService;
use App\Twig\DexCategoryExtension;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Twig\TwigFilter;

/**
 * @internal
 */
#[CoversClass(DexCategoryExtension::class)]
final class DexCategoryExtensionTest extends TestCase
{
    #[Test]
    public function getFilters(): void
    {
        $service = $this->createStub(DexCategoryGroupService::class);

        $filters = new DexCategoryExtension($service)->getFilters();

        $this->assertCount(1, $filters);
        $this->assertInstanceOf(TwigFilter::class, $filters[0]);
        $this->assertSame('dex_category_groups', $filters[0]->getName());
        $this->assertSame([$service, 'group'], $filters[0]->getCallable());
    }
}
