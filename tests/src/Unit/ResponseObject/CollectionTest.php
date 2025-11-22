<?php

declare(strict_types=1);

namespace App\Tests\Unit\ResponseObject;

use App\ResponseObject\Collection;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(Collection::class)]
class CollectionTest extends TestCase
{
    public function testConstructor(): void
    {
        $object = new Collection(
            'Toto',
            'Tautaux',
            'toto',
            12,
        );

        $this->assertSame('Toto', $object->getName());
        $this->assertSame('Tautaux', $object->getFrenchName());
        $this->assertSame('toto', $object->getSlug());
        $this->assertSame(12, $object->getOrderNumber());
    }
}
