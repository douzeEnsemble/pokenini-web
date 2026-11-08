<?php

declare(strict_types=1);

namespace App\Tests\Unit\ResponseObject\Label;

use App\ResponseObject\Label\Collection;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(Collection::class)]
final class CollectionTest extends TestCase
{
    #[Test]
    public function constructor(): void
    {
        $object = new Collection(
            'Toto',
            'Tautaux',
            'toto',
            11,
        );

        $this->assertSame('Toto', $object->getName());
        $this->assertSame('Tautaux', $object->getFrenchName());
        $this->assertSame(['en' => 'Toto', 'fr' => 'Tautaux'], $object->getNames());
        $this->assertSame('toto', $object->getSlug());
        $this->assertSame(11, $object->getOrderNumber());
    }

    #[Test]
    public function inASingleLanguage(): void
    {
        $object = new Collection('Boss', null, 'boss', 11);

        $this->assertSame('Boss', $object->getName());
        $this->assertNull($object->getFrenchName());
        $this->assertSame(['*' => 'Boss'], $object->getNames());
    }
}
