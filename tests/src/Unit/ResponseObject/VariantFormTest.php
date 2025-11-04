<?php

declare(strict_types=1);

namespace App\Tests\Unit\ResponseObject;

use App\ResponseObject\AbstractForm;
use App\ResponseObject\VariantForm;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(AbstractForm::class)]
#[CoversClass(VariantForm::class)]
class VariantFormTest extends TestCase
{
    public function testConstructor()
    {
        $object = new VariantForm(
            'Toto',
            'Tautaux',
            'toto',
        );

        $this->assertSame('Toto', $object->getName());
        $this->assertSame('Tautaux', $object->getFrenchName());
        $this->assertSame('toto', $object->getSlug());
    }
}
