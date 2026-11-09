<?php

declare(strict_types=1);

namespace App\Tests\Unit\ResponseObject\Album;

use App\ResponseObject\Album\DexRegion;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(DexRegion::class)]
final class DexRegionTest extends TestCase
{
    #[Test]
    public function getters(): void
    {
        $region = new DexRegion('Kanto', 'Kanto');

        $this->assertSame('Kanto', $region->getName());
        $this->assertSame('Kanto', $region->getFrenchName());
        $this->assertSame(['en' => 'Kanto', 'fr' => 'Kanto'], $region->getNames());
    }

    #[Test]
    public function inASingleLanguage(): void
    {
        $region = new DexRegion('Hoenn', null);

        $this->assertNull($region->getFrenchName());
        $this->assertSame(['*' => 'Hoenn'], $region->getNames());
    }
}
