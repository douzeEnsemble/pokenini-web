<?php

declare(strict_types=1);

namespace App\Tests\Unit\Controller;

use App\Controller\TrainerDexLinkController;
use App\ResponseObject\Album\TrainerDexLink;
use App\Service\Back\TrainerDexLinkService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\HttpClient\Exception\HttpExceptionInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * @internal
 */
#[CoversClass(TrainerDexLinkController::class)]
final class TrainerDexLinkControllerTest extends TestCase
{
    public function testList(): void
    {
        $link = new TrainerDexLink('link-1', 'to', 'shiny', 'Shiny Living', 'Vivarium Chromatique');

        $service = $this->createMock(TrainerDexLinkService::class);
        $service->expects($this->once())
            ->method('list')
            ->with('national')
            ->willReturn([$link])
        ;

        $serializer = $this->createMock(SerializerInterface::class);
        $serializer->expects($this->once())
            ->method('serialize')
            ->with([$link], 'json')
            ->willReturn('[{"id":"link-1"}]')
        ;

        $controller = new TrainerDexLinkController($service, $serializer);

        $response = $controller->list('national');

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('[{"id":"link-1"}]', $response->getContent());
    }

    public function testListForwardsApiFailureStatusCode(): void
    {
        $apiResponse = $this->createMock(ResponseInterface::class);
        $apiResponse->method('getStatusCode')->willReturn(404);

        $exception = $this->createMock(HttpExceptionInterface::class);
        $exception->method('getResponse')->willReturn($apiResponse);

        $service = $this->createMock(TrainerDexLinkService::class);
        $service->method('list')->willThrowException($exception);

        $serializer = $this->createMock(SerializerInterface::class);
        $serializer->expects($this->never())->method('serialize');

        $controller = new TrainerDexLinkController($service, $serializer);

        $response = $controller->list('national');

        $this->assertSame(404, $response->getStatusCode());
    }

    public function testCreateRejectsEmptyBody(): void
    {
        $service = $this->createMock(TrainerDexLinkService::class);
        $service->expects($this->never())->method('create');

        $serializer = $this->createMock(SerializerInterface::class);
        $serializer->expects($this->never())->method('serialize');

        $controller = new TrainerDexLinkController($service, $serializer);

        $response = $controller->create('national', Request::create('test.local', 'POST', content: ''));

        $this->assertSame(400, $response->getStatusCode());
    }

    public function testCreateSucceeds(): void
    {
        $service = $this->createMock(TrainerDexLinkService::class);
        $service->expects($this->once())
            ->method('create')
            ->with('national', '{"targetDexSlug":"shiny"}')
        ;

        $serializer = $this->createMock(SerializerInterface::class);
        $serializer->expects($this->never())->method('serialize');

        $controller = new TrainerDexLinkController($service, $serializer);

        $response = $controller->create('national', Request::create('test.local', 'POST', content: '{"targetDexSlug":"shiny"}'));

        $this->assertSame(201, $response->getStatusCode());
    }

    public function testCreateForwardsApiFailureStatusCode(): void
    {
        $apiResponse = $this->createMock(ResponseInterface::class);
        $apiResponse->method('getStatusCode')->willReturn(409);

        $exception = $this->createMock(HttpExceptionInterface::class);
        $exception->method('getResponse')->willReturn($apiResponse);

        $service = $this->createMock(TrainerDexLinkService::class);
        $service->method('create')->willThrowException($exception);

        $serializer = $this->createMock(SerializerInterface::class);
        $serializer->expects($this->never())->method('serialize');

        $controller = new TrainerDexLinkController($service, $serializer);

        $response = $controller->create('national', Request::create('test.local', 'POST', content: '{"targetDexSlug":"shiny"}'));

        $this->assertSame(409, $response->getStatusCode());
    }

    public function testDelete(): void
    {
        $service = $this->createMock(TrainerDexLinkService::class);
        $service->expects($this->once())->method('delete')->with('link-1');

        $serializer = $this->createMock(SerializerInterface::class);
        $serializer->expects($this->never())->method('serialize');

        $controller = new TrainerDexLinkController($service, $serializer);

        $response = $controller->delete('link-1');

        $this->assertSame(200, $response->getStatusCode());
    }

    public function testDeleteForwardsApiFailureStatusCode(): void
    {
        $apiResponse = $this->createMock(ResponseInterface::class);
        $apiResponse->method('getStatusCode')->willReturn(404);

        $exception = $this->createMock(HttpExceptionInterface::class);
        $exception->method('getResponse')->willReturn($apiResponse);

        $service = $this->createMock(TrainerDexLinkService::class);
        $service->method('delete')->willThrowException($exception);

        $serializer = $this->createMock(SerializerInterface::class);
        $serializer->expects($this->never())->method('serialize');

        $controller = new TrainerDexLinkController($service, $serializer);

        $response = $controller->delete('link-1');

        $this->assertSame(404, $response->getStatusCode());
    }
}
