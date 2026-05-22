<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Back;

use App\ResponseObject\Label\Labels;
use App\Security\UserTokenServiceInterface;
use App\Service\Back\AbstractBackService;
use App\Service\Back\GetLabelsService;
use App\Tests\Common\Traits\ResponseObjectTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use Psr\Log\LoggerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * @internal
 */
#[CoversClass(GetLabelsService::class)]
final class GetLabelsServiceTest extends AbstractTestBackService
{
    use ResponseObjectTrait;

    public const ENDPOINT = 'labels';
    public const RESPONSE_CONTENT = '/app/tests/resources/unit/service/back/labels.json';

    public function testGet(): void
    {
        $json = (new Filesystem())->readFile(self::RESPONSE_CONTENT);

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
        $json = (new Filesystem())->readFile(self::RESPONSE_CONTENT);

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

    #[\Override]
    protected function instanciateService(
        LoggerInterface $logger,
        HttpClientInterface $client,
        string $url,
        string $cafilePath,
        UserTokenServiceInterface $userTokenService,
        SerializerInterface $serializer,
    ): AbstractBackService {
        return new GetLabelsService(
            $logger,
            $client,
            $url,
            $cafilePath,
            $userTokenService,
            $serializer,
        );
    }
}
