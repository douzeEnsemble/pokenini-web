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
 *
 * @SuppressWarnings("PHPMD.CouplingBetweenObjects")
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

    public function testGetReturnsNullWhenNoRunExistsWithSurroundingWhitespace(): void
    {
        // Proves the trim() call matters: pokenini-back's JsonResponse body
        // could plausibly include trailing whitespace/newlines depending on
        // how it's serialized, and this must still be treated as "no run".
        $service = $this->getService(" {}\n", 'https://back.domain/istration/action/trigger/update_images/status');

        $this->assertNull($service->get(false));
    }

    public function testGetDeserializesStatus(): void
    {
        $json = <<<'JSON'
            {
                "correlation_id": "corr-1",
                "workflow_a": {"state": "done", "url": "https://github.com/x/y/actions/runs/1"},
                "icon_pr": {"state": "merged", "url": "https://github.com/x/y/pull/2"},
                "workflow_b": {"state": "idle", "url": null},
                "resources_pr": {"state": "idle", "url": null}
            }
            JSON;

        $service = $this->getService($json, 'https://back.domain/istration/action/trigger/update_images/status');

        $status = $service->get(false);

        $this->assertNotNull($status);
        $this->assertSame('corr-1', $status->correlationId);
        $this->assertSame('done', $status->workflowA->state);
        $this->assertSame('https://github.com/x/y/actions/runs/1', $status->workflowA->url);
        $this->assertSame('merged', $status->iconPr->state);
        $this->assertSame('https://github.com/x/y/pull/2', $status->iconPr->url);
        $this->assertSame('idle', $status->workflowB->state);
        $this->assertNull($status->workflowB->url);
        $this->assertSame('idle', $status->resourcesPr->state);
        $this->assertNull($status->resourcesPr->url);
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
