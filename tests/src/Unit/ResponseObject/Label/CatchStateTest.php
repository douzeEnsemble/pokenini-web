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
            '#ffffff',
        );

        $this->assertSame('Toto', $object->getName());
        $this->assertSame('Tautaux', $object->getFrenchName());
        $this->assertSame(['en' => 'Toto', 'fr' => 'Tautaux'], $object->getNames());
        $this->assertSame('toto', $object->getSlug());
        $this->assertSame('#blouge', $object->getColor());
        $this->assertSame('#ffffff', $object->getTextColor());
    }

    #[Test]
    public function withoutATextColor(): void
    {
        // An API that doesn't send it yet: the page falls back to a computed color
        $object = new CatchState('No', 'Non', 'no', '#e57373');

        $this->assertNull($object->getTextColor());
    }

    #[Test]
    public function inASingleLanguage(): void
    {
        $object = new CatchState('Non', null, 'no', '#e57373');

        $this->assertSame('Non', $object->getName());
        $this->assertNull($object->getFrenchName());
        $this->assertSame(['*' => 'Non'], $object->getNames());
    }
}
