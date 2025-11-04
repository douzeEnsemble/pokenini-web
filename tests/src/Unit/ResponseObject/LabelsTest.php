<?php

declare(strict_types=1);

namespace App\Tests\Unit\ResponseObject;

use App\ResponseObject\CatchState;
use App\ResponseObject\CategoryForm;
use App\ResponseObject\Collection;
use App\ResponseObject\GameBundle;
use App\ResponseObject\Labels;
use App\ResponseObject\RegionalForm;
use App\ResponseObject\SpecialForm;
use App\ResponseObject\Type;
use App\ResponseObject\VariantForm;
use App\Tests\Common\Traits\ResponseObjectTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(Labels::class)]
class LabelsTest extends TestCase
{
    use ResponseObjectTrait;

    public function testConstructor()
    {
        $object = $this->getStubLabels();

        $this->assertCount(1, $object->getCatchStates());
        $this->assertCount(2, $object->getTypes());
        $this->assertCount(3, $object->getCategoryForms());
        $this->assertCount(4, $object->getRegionalForms());
        $this->assertCount(5, $object->getSpecialForms());
        $this->assertCount(6, $object->getVariantForms());
        $this->assertCount(7, $object->getGameBundles());
        $this->assertCount(8, $object->getCollections());      
    }


    public function testConstructorWithAllEmpty()
    {
        $object = new Labels(
            [],
            [],
            [],
            [],
            [],
            [],
            [],
            [],
        );

        $this->assertSame([], $object->getCatchStates());
        $this->assertSame([], $object->getTypes());
        $this->assertSame([], $object->getCategoryForms());
        $this->assertSame([], $object->getRegionalForms());
        $this->assertSame([], $object->getSpecialForms());
        $this->assertSame([], $object->getVariantForms());
        $this->assertSame([], $object->getGameBundles());
        $this->assertSame([], $object->getCollections());      
    }
}
