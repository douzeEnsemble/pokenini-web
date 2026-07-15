<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Back;

use App\Exception\NoLoggedUserException;
use App\Security\UserTokenServiceInterface;
use App\Service\Back\GetImagePipelineStatusService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactory;
use Symfony\Component\Serializer\Mapping\Loader\AttributeLoader;
use Symfony\Component\Serializer\NameConverter\MetadataAwareNameConverter;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * @internal
 */
#[CoversClass(GetImagePipelineStatusService::class)]
final class GetImagePipelineStatusServiceTest extends TestCase
{
    public function testGetReturnsNullWhenNoRunExists(): void
    {
        $service = $this->getService('{}', 'https://back.domain/istration/action/trigger/update_images/status');

        $this->assertNull($service->get(false));
    }

    public function testGetWithRefreshAppendsQueryParam(): void
    {
        $service = $this->getService('{}', 'https://back.domain/istration/action/trigger/update_images/status?refresh=1');

        $this->assertNull($service->get(true));
    }

    public function testGetDeserializesStatus(): void
    {
        $json = <<<'JSON'
            {
                "correlationId": "corr-1",
                "workflowA": {"state": "done", "url": "https://github.com/x/y/actions/runs/1"},
                "iconPr": {"state": "merged", "url": "https://github.com/x/y/pull/2"},
                "workflowB": {"state": "idle", "url": null},
                "resourcesPr": {"state": "idle", "url": null}
            }
            JSON;

        $service = $this->getService($json, 'https://back.domain/istration/action/trigger/update_images/status');

        $status = $service->get(false);

        $this->assertNotNull($status);
        $this->assertSame('corr-1', $status->correlationId);
        $this->assertSame('done', $status->workflowA->state);
        $this->assertSame('merged', $status->iconPr->state);
    }

    private function getService(string $responseBody, string $expectedUrl): GetImagePipelineStatusService
    {
        $response = $this->createStub(ResponseInterface::class);
        $response->method('getContent')->willReturn($responseBody);
        $response->method('getStatusCode')->willReturn(200);

        $client = $this->createMock(HttpClientInterface::class);
        $client
            ->expects($this->once())
            ->method('request')
            ->with('GET', $expectedUrl, $this->anything())
            ->willReturn($response)
        ;

        $userTokenService = $this->createStub(UserTokenServiceInterface::class);
        $userTokenService
            ->method('getLoggedUser')
            ->willThrowException(new NoLoggedUserException('No user logged'))
        ;

        return new GetImagePipelineStatusService(
            $this->createStub(LoggerInterface::class),
            $client,
            'https://back.domain',
            './resources/certificates/cacert.pem',
            $userTokenService,
            $this->buildSerializer(),
        );
    }

    private function buildSerializer(): SerializerInterface
    {
        $classMetadataFactory = new ClassMetadataFactory(new AttributeLoader());
        $nameConverter = new MetadataAwareNameConverter($classMetadataFactory);

        return new Serializer(
            [
                new DateTimeNormalizer(),
                new ObjectNormalizer($classMetadataFactory, $nameConverter),
            ],
            [new JsonEncoder()]
        );
    }
}
