<?php

declare(strict_types=1);

namespace App\Tests\Integration\ResponseObject\Label;

use App\ResponseObject\Label\CatchState;
use App\ResponseObject\Label\CategoryForm;
use App\ResponseObject\Label\Collection;
use App\ResponseObject\Label\GameBundle;
use App\ResponseObject\Label\Labels;
use App\ResponseObject\Label\RegionalForm;
use App\ResponseObject\Label\SpecialForm;
use App\ResponseObject\Label\Type;
use App\ResponseObject\Label\VariantForm;
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

        $json = (string) file_get_contents('/app/tests/resources/integration/back/labels.json');

        $object = $serializer->deserialize($json, Labels::class, 'json');

        $this->assertCount(6, $object->getCatchStates());
        $this->assertContainsOnlyInstancesOf(CatchState::class, $object->getCatchStates());

        $this->assertCount(18, $object->getTypes());
        $this->assertContainsOnlyInstancesOf(Type::class, $object->getTypes());

        $this->assertCount(6, $object->getCategoryForms());
        $this->assertContainsOnlyInstancesOf(CategoryForm::class, $object->getCategoryForms());

        $this->assertCount(4, $object->getRegionalForms());
        $this->assertContainsOnlyInstancesOf(RegionalForm::class, $object->getRegionalForms());

        $this->assertCount(7, $object->getSpecialForms());
        $this->assertContainsOnlyInstancesOf(SpecialForm::class, $object->getSpecialForms());

        $this->assertCount(7, $object->getVariantForms());
        $this->assertContainsOnlyInstancesOf(VariantForm::class, $object->getVariantForms());

        $this->assertCount(18, $object->getGameBundles());
        $this->assertContainsOnlyInstancesOf(GameBundle::class, $object->getGameBundles());

        $this->assertCount(8, $object->getCollections());
        $this->assertContainsOnlyInstancesOf(Collection::class, $object->getCollections());
    }
}
