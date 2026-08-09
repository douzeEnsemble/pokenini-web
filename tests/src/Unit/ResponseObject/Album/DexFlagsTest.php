<?php

declare(strict_types=1);

namespace App\Tests\Unit\ResponseObject\Album;

use App\ResponseObject\Album\DexFlags;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(DexFlags::class)]
final class DexFlagsTest extends TestCase
{
    #[Test]
    public function getters(): void
    {
        $flags = new DexFlags(
            isShiny: true,
            isPrivate: false,
            isOnHome: true,
            isDisplayForm: true,
            isReleased: true,
            isPremium: false,
            isCustom: true,
        );

        $this->assertTrue($flags->isShiny());
        $this->assertFalse($flags->isPrivate());
        $this->assertTrue($flags->isOnHome());
        $this->assertTrue($flags->isDisplayForm());
        $this->assertTrue($flags->isReleased());
        $this->assertFalse($flags->isPremium());
        $this->assertTrue($flags->isCustom());
    }
}
