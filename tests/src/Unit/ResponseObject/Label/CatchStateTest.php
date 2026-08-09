<?php

declare(strict_types=1);

namespace App\Tests\Unit\ResponseObject\Label;

use App\ResponseObject\Label\CatchState;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(CatchState::class)]
final class CatchStateTest extends TestCase
{
    #[Test]
    public function constructor(): void
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
