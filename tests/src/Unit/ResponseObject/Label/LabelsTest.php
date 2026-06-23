<?php

declare(strict_types=1);

namespace App\Tests\Unit\ResponseObject\Label;

use App\ResponseObject\Label\Forms;
use App\ResponseObject\Label\Labels;
use App\Tests\Common\Traits\ResponseObjectTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(Labels::class)]
final class LabelsTest extends TestCase
{
    use ResponseObjectTrait;

    public function testConstructor(): void
    {
        $object = $this->getStubLabels();

        $this->assertCount(1, $object->getCatchStates());
        $this->assertCount(2, $object->getTypes());
        $this->assertCount(3, $object->getForms()->getCategory());
        $this->assertCount(4, $object->getForms()->getRegional());
        $this->assertCount(5, $object->getForms()->getSpecial());
        $this->assertCount(6, $object->getForms()->getVariant());
        $this->assertCount(7, $object->getGameBundles());
        $this->assertCount(8, $object->getCollections());
    }

    public function testConstructorWithAllEmpty(): void
    {
        $object = new Labels(
            [],
            [],
            new Forms([], [], [], []),
            [],
            [],
        );

        $this->assertSame([], $object->getCatchStates());
        $this->assertSame([], $object->getTypes());
        $this->assertSame([], $object->getForms()->getCategory());
        $this->assertSame([], $object->getForms()->getRegional());
        $this->assertSame([], $object->getForms()->getSpecial());
        $this->assertSame([], $object->getForms()->getVariant());
        $this->assertSame([], $object->getGameBundles());
        $this->assertSame([], $object->getCollections());
    }
}
