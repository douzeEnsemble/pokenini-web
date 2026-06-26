<?php

declare(strict_types=1);

namespace App\Tests\Unit\ResponseObject\Album;

use App\ResponseObject\Album\DexRegion;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(DexRegion::class)]
final class DexRegionTest extends TestCase
{
    public function testGetters(): void
    {
        $region = new DexRegion('Kanto', 'Kanto');

        $this->assertSame('Kanto', $region->getName());
        $this->assertSame('Kanto', $region->getFrenchName());
    }
}
