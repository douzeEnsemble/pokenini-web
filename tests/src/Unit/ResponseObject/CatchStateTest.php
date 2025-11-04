<?php

declare(strict_types=1);

namespace App\Tests\Unit\ResponseObject;

use App\ResponseObject\CatchState;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(CatchState::class)]
class CatchStateTest extends TestCase
{
    public function testConstructor()
    {
        $object = new CatchState(
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
