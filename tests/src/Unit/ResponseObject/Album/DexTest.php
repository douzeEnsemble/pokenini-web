<?php

declare(strict_types=1);

namespace App\Tests\Unit\ResponseObject\Album;

use App\ResponseObject\Album\Dex;
use App\ResponseObject\Album\DexFlags;
use App\ResponseObject\Album\DexRegion;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(Dex::class)]
final class DexTest extends TestCase
{
    #[Test]
    public function inTwoLanguages(): void
    {
        $dex = $this->dex('Stub', 'Bout', 'Stub of south', 'Bout du Sud', new DexRegion('South', 'Sud'));

        $this->assertSame('Bout', $dex->getFrenchName());
        $this->assertSame('Bout du Sud', $dex->getFrenchDescription());
        $this->assertSame(['en' => 'Stub', 'fr' => 'Bout'], $dex->getNames());
        $this->assertSame(['en' => 'Stub of south', 'fr' => 'Bout du Sud'], $dex->getDescriptions());
        $this->assertSame('South', $dex->getRegionName());
        $this->assertSame('Sud', $dex->getRegionFrenchName());
        $this->assertSame(['en' => 'South', 'fr' => 'Sud'], $dex->getRegionNames());
    }

    #[Test]
    public function inASingleLanguage(): void
    {
        $dex = $this->dex('Bout', null, 'Bout du Sud', null, new DexRegion('Sud', null));

        $this->assertNull($dex->getFrenchName());
        $this->assertNull($dex->getFrenchDescription());
        $this->assertSame(['*' => 'Bout'], $dex->getNames());
        $this->assertSame(['*' => 'Bout du Sud'], $dex->getDescriptions());
        $this->assertNull($dex->getRegionFrenchName());
        $this->assertSame(['*' => 'Sud'], $dex->getRegionNames());
    }

    private function dex(string $name, ?string $frenchName, string $description, ?string $frenchDescription, DexRegion $region): Dex
    {
        return new Dex(
            'stubby',
            'stub',
            $name,
            $frenchName,
            new DexFlags(
                isShiny: true,
                isPrivate: false,
                isOnHome: true,
                isDisplayForm: true,
                isReleased: true,
                isPremium: false,
                isCustom: true,
            ),
            null,
            $region,
            'list',
            $description,
            $frenchDescription,
            '546.46545',
        );
    }
}
