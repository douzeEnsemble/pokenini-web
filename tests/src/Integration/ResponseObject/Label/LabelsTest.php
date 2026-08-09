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
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
#[CoversClass(Labels::class)]
final class LabelsTest extends KernelTestCase
{
    #[Test]
    public function deserialize(): void
    {
        self::bootKernel();

        /** @var SerializerInterface $serializer */
        $serializer = self::getContainer()->get(SerializerInterface::class);

        $json = (new Filesystem())->readFile('/app/tests/resources/integration/back/labels.json');

        $object = $serializer->deserialize($json, Labels::class, 'json');

        $this->assertCount(6, $object->getCatchStates());
        $this->assertContainsOnlyInstancesOf(CatchState::class, $object->getCatchStates());

        $this->assertCount(18, $object->getTypes());
        $this->assertContainsOnlyInstancesOf(Type::class, $object->getTypes());

        $this->assertCount(6, $object->getForms()->getCategory());
        $this->assertContainsOnlyInstancesOf(CategoryForm::class, $object->getForms()->getCategory());

        $this->assertCount(4, $object->getForms()->getRegional());
        $this->assertContainsOnlyInstancesOf(RegionalForm::class, $object->getForms()->getRegional());

        $this->assertCount(7, $object->getForms()->getSpecial());
        $this->assertContainsOnlyInstancesOf(SpecialForm::class, $object->getForms()->getSpecial());

        $this->assertCount(7, $object->getForms()->getVariant());
        $this->assertContainsOnlyInstancesOf(VariantForm::class, $object->getForms()->getVariant());

        $this->assertCount(18, $object->getGameBundles());
        $this->assertContainsOnlyInstancesOf(GameBundle::class, $object->getGameBundles());

        $this->assertCount(8, $object->getCollections());
        $this->assertContainsOnlyInstancesOf(Collection::class, $object->getCollections());
    }
}
