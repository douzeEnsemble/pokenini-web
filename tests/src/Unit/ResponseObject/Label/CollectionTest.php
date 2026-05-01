<?php

declare(strict_types=1);

namespace App\Tests\Unit\ResponseObject\Label;

use App\ResponseObject\Label\Collection;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(Collection::class)]
final class CollectionTest extends TestCase
{
    public function testConstructor(): void
    {
        $object = new Collection(
            'Toto',
            'Tautaux',
            'toto',
        );

        $this->assertSame('Toto', $object->getName());
        $this->assertSame('Tautaux', $object->getFrenchName());
        $this->assertSame('toto', $object->getSlug());
    }
}
