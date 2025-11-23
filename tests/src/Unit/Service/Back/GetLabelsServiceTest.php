<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Back;

use App\ResponseObject\Label\Labels;
use App\Service\Back\GetLabelsService;
use App\Tests\Common\Traits\ResponseObjectTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
#[CoversClass(GetLabelsService::class)]
class GetLabelsServiceTest extends TestCase
{
    use BackServiceTrait;
    use ResponseObjectTrait;

    public const ENDPOINT = 'labels';
    public const RESPONSE_CONTENT = '/var/www/html/tests/resources/unit/service/back/labels.json';

    public function testGet(): void
    {
        $json = (string) file_get_contents(self::RESPONSE_CONTENT);

        $labels = $this->getStubLabels();

        $serializer = $this->createMock(SerializerInterface::class);
        $serializer
            ->expects($this->once())
            ->method('deserialize')
            ->with(
                $json,
                Labels::class,
                'json',
            )
            ->willReturn($labels)
        ;

        /** @var GetLabelsService $service */
        $service = $this->getServiceWithLoggedUser(
            GetLabelsService::class,
            'GET',
            $json,
            self::ENDPOINT,
            [],
            $serializer,
        );

        $object = $service->get();

        $this->assertCount(1, $object->getCatchStates());
        $this->assertCount(2, $object->getTypes());
        $this->assertCount(3, $object->getCategoryForms());
        $this->assertCount(4, $object->getRegionalForms());
        $this->assertCount(5, $object->getSpecialForms());
        $this->assertCount(6, $object->getVariantForms());
        $this->assertCount(7, $object->getGameBundles());
        $this->assertCount(8, $object->getCollections());
    }

    public function testWithoutLoggedUser(): void
    {
        $json = (string) file_get_contents(self::RESPONSE_CONTENT);

        $labels = $this->getStubLabels();

        $serializer = $this->createMock(SerializerInterface::class);
        $serializer
            ->expects($this->once())
            ->method('deserialize')
            ->with(
                $json,
                Labels::class,
                'json',
            )
            ->willReturn($labels)
        ;

        /** @var GetLabelsService $service */
        $service = $this->getServiceWithoutLoggedUser(
            GetLabelsService::class,
            'GET',
            $json,
            self::ENDPOINT,
            [],
            $serializer,
        );

        $object = $service->get();

        $this->assertCount(1, $object->getCatchStates());
        $this->assertCount(2, $object->getTypes());
        $this->assertCount(3, $object->getCategoryForms());
        $this->assertCount(4, $object->getRegionalForms());
        $this->assertCount(5, $object->getSpecialForms());
        $this->assertCount(6, $object->getVariantForms());
        $this->assertCount(7, $object->getGameBundles());
        $this->assertCount(8, $object->getCollections());
    }
}
