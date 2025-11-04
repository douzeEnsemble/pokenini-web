<?php

declare(strict_types=1);

namespace App\Tests\Unit\ResponseObject;

use App\ResponseObject\AbstractForm;
use App\ResponseObject\RegionalForm;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(AbstractForm::class)]
#[CoversClass(RegionalForm::class)]
class RegionalFormTest extends TestCase
{
    public function testConstructor()
    {
        $object = new RegionalForm(
            'Toto',
            'Tautaux',
            'toto',
        );

        $this->assertSame('Toto', $object->getName());
        $this->assertSame('Tautaux', $object->getFrenchName());
        $this->assertSame('toto', $object->getSlug());
    }
}
