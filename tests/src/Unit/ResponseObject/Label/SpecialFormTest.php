<?php

declare(strict_types=1);

namespace App\Tests\Unit\ResponseObject\Label;

use App\ResponseObject\Label\AbstractForm;
use App\ResponseObject\Label\SpecialForm;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(AbstractForm::class)]
#[CoversClass(SpecialForm::class)]
final class SpecialFormTest extends TestCase
{
    public function testConstructor(): void
    {
        $object = new SpecialForm(
            'Toto',
            'Tautaux',
            'toto',
        );

        $this->assertSame('Toto', $object->getName());
        $this->assertSame('Tautaux', $object->getFrenchName());
        $this->assertSame('toto', $object->getSlug());
    }
}
