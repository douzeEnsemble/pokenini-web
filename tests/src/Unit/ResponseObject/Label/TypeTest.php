<?php

declare(strict_types=1);

namespace App\Tests\Unit\ResponseObject\Label;

use App\ResponseObject\Label\Type;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(Type::class)]
final class TypeTest extends TestCase
{
    #[Test]
    public function constructor(): void
    {
        $object = new Type(
            'Toto',
            'Tautaux',
            'toto',
            '#blouge',
        );

        $this->assertSame('Toto', $object->getName());
        $this->assertSame('Tautaux', $object->getFrenchName());
        $this->assertSame(['en' => 'Toto', 'fr' => 'Tautaux'], $object->getNames());
        $this->assertSame('toto', $object->getSlug());
        $this->assertSame('#blouge', $object->getColor());
    }

    #[Test]
    public function inASingleLanguage(): void
    {
        $object = new Type('Combat', null, 'fighting', '#cf4069');

        $this->assertSame('Combat', $object->getName());
        $this->assertNull($object->getFrenchName());
        $this->assertSame(['*' => 'Combat'], $object->getNames());
    }
}
