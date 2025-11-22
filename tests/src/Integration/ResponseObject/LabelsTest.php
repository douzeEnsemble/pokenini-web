<?php

declare(strict_types=1);

namespace App\Tests\Integration\ResponseObject;

use App\ResponseObject\CatchState;
use App\ResponseObject\CategoryForm;
use App\ResponseObject\Collection;
use App\ResponseObject\GameBundle;
use App\ResponseObject\Labels;
use App\ResponseObject\RegionalForm;
use App\ResponseObject\SpecialForm;
use App\ResponseObject\Type;
use App\ResponseObject\VariantForm;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
#[CoversClass(Labels::class)]
class LabelsTest extends KernelTestCase
{
    public function testDeserialize(): void
    {
        self::bootKernel();

        /** @var SerializerInterface $serializer */
        $serializer = static::getContainer()->get(SerializerInterface::class);

        $json = (string) file_get_contents('/var/www/html/tests/resources/integration/back/labels.json');

        /** @var Labels $object */
        $object = $serializer->deserialize($json, Labels::class, 'json');

        $this->assertCount(6, $object->getCatchStates());
        foreach ($object->getCatchStates() as $item) {
            $this->assertInstanceOf(CatchState::class, $item);
        }

        $this->assertCount(18, $object->getTypes());
        foreach ($object->getTypes() as $item) {
            $this->assertInstanceOf(Type::class, $item);
        }

        $this->assertCount(6, $object->getCategoryForms());
        foreach ($object->getCategoryForms() as $item) {
            $this->assertInstanceOf(CategoryForm::class, $item);
        }

        $this->assertCount(4, $object->getRegionalForms());
        foreach ($object->getRegionalForms() as $item) {
            $this->assertInstanceOf(RegionalForm::class, $item);
        }

        $this->assertCount(7, $object->getSpecialForms());
        foreach ($object->getSpecialForms() as $item) {
            $this->assertInstanceOf(SpecialForm::class, $item);
        }

        $this->assertCount(7, $object->getVariantForms());
        foreach ($object->getVariantForms() as $item) {
            $this->assertInstanceOf(VariantForm::class, $item);
        }

        $this->assertCount(18, $object->getGameBundles());
        foreach ($object->getGameBundles() as $item) {
            $this->assertInstanceOf(GameBundle::class, $item);
        }

        $this->assertCount(8, $object->getCollections());
        foreach ($object->getCollections() as $item) {
            $this->assertInstanceOf(Collection::class, $item);
        }
    }
}
