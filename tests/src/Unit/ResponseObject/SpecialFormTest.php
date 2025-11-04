<?php

declare(strict_types=1);

namespace App\Tests\Unit\ResponseObject;

use App\ResponseObject\AbstractForm;
use App\ResponseObject\SpecialForm;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(AbstractForm::class)]
#[CoversClass(SpecialForm::class)]
class SpecialFormTest extends TestCase
{
    public function testConstructor()
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
