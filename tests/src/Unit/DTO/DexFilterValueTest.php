<?php

declare(strict_types=1);

namespace App\Tests\Unit\DTO;

use App\DTO\DexFilterValue;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(DexFilterValue::class)]
class DexFilterValueTest extends TestCase
{
    public function testConstruct(): void
    {
        $dexFilterValue = new DexFilterValue(true);

        $this->assertTrue($dexFilterValue->value);
    }
}
