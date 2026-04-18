<?php

declare(strict_types=1);

namespace App\Tests\Unit\ResponseObject\Label;

use App\ResponseObject\Label\Type;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(Type::class)]
final class TypeTest extends TestCase
{
    public function testConstructor(): void
    {
        $object = new Type(
            'Toto',
            'Tautaux',
            'toto',
            '#blouge',
        );

        $this->assertSame('Toto', $object->getName());
        $this->assertSame('Tautaux', $object->getFrenchName());
        $this->assertSame('toto', $object->getSlug());
        $this->assertSame('#blouge', $object->getColor());
    }
}
