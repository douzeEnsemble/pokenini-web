<?php

declare(strict_types=1);

namespace App\Tests\Unit\ResponseObject\Album;

use App\ResponseObject\Album\DexListItemRef;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(DexListItemRef::class)]
final class DexListItemRefTest extends TestCase
{
    public function testGetters(): void
    {
        $ref = new DexListItemRef(slug: 'homepokemongo');

        $this->assertSame('homepokemongo', $ref->getSlug());
    }
}
