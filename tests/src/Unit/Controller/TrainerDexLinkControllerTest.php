<?php

declare(strict_types=1);

namespace App\Tests\Unit\Controller;

use App\Controller\TrainerDexLinkController;
use App\Service\Back\TrainerDexLinkService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\HttpClient\Exception\HttpExceptionInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * @internal
 */
#[CoversClass(TrainerDexLinkController::class)]
final class TrainerDexLinkControllerTest extends TestCase
{
    #[Test]
    public function list(): void
    {
        $service = $this->createMock(TrainerDexLinkService::class);
        $service->expects($this->once())
            ->method('listAsJson')
            ->with('national')
            ->willReturn('[{"id":"link-1"}]')
        ;

        $controller = new TrainerDexLinkController($service);

        $response = $controller->list('national');

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('[{"id":"link-1"}]', $response->getContent());
    }

    #[Test]
    public function listForwardsApiFailureStatusCode(): void
    {
        $apiResponse = $this->createStub(ResponseInterface::class);
        $apiResponse->method('getStatusCode')->willReturn(404);

        $exception = $this->createStub(HttpExceptionInterface::class);
        $exception->method('getResponse')->willReturn($apiResponse);

        $service = $this->createMock(TrainerDexLinkService::class);
        $service->expects($this->once())
            ->method('listAsJson')
            ->with('national')
            ->willThrowException($exception)
        ;

        $controller = new TrainerDexLinkController($service);

        $response = $controller->list('national');

        $this->assertSame(404, $response->getStatusCode());
    }

    #[Test]
    public function createRejectsEmptyBody(): void
    {
        $service = $this->createMock(TrainerDexLinkService::class);
        $service->expects($this->never())->method('create');

        $controller = new TrainerDexLinkController($service);

        $response = $controller->create('national', Request::create('test.local', 'POST', content: ''));

        $this->assertSame(400, $response->getStatusCode());
    }

    #[Test]
    public function createSucceeds(): void
    {
        $service = $this->createMock(TrainerDexLinkService::class);
        $service->expects($this->once())
            ->method('create')
            ->with('national', '{"targetDexSlug":"shiny"}')
        ;

        $controller = new TrainerDexLinkController($service);

        $response = $controller->create('national', Request::create('test.local', 'POST', content: '{"targetDexSlug":"shiny"}'));

        $this->assertSame(201, $response->getStatusCode());
    }

    #[Test]
    public function createForwardsApiFailureStatusCode(): void
    {
        $apiResponse = $this->createStub(ResponseInterface::class);
        $apiResponse->method('getStatusCode')->willReturn(409);

        $exception = $this->createStub(HttpExceptionInterface::class);
        $exception->method('getResponse')->willReturn($apiResponse);

        $service = $this->createMock(TrainerDexLinkService::class);
        $service->expects($this->once())
            ->method('create')
            ->with('national', '{"targetDexSlug":"shiny"}')
            ->willThrowException($exception)
        ;

        $controller = new TrainerDexLinkController($service);

        $response = $controller->create('national', Request::create('test.local', 'POST', content: '{"targetDexSlug":"shiny"}'));

        $this->assertSame(409, $response->getStatusCode());
    }

    #[Test]
    public function delete(): void
    {
        $service = $this->createMock(TrainerDexLinkService::class);
        $service->expects($this->once())->method('delete')->with('link-1');

        $controller = new TrainerDexLinkController($service);

        $response = $controller->delete('link-1');

        $this->assertSame(200, $response->getStatusCode());
    }

    #[Test]
    public function deleteForwardsApiFailureStatusCode(): void
    {
        $apiResponse = $this->createStub(ResponseInterface::class);
        $apiResponse->method('getStatusCode')->willReturn(404);

        $exception = $this->createStub(HttpExceptionInterface::class);
        $exception->method('getResponse')->willReturn($apiResponse);

        $service = $this->createMock(TrainerDexLinkService::class);
        $service->expects($this->once())
            ->method('delete')
            ->with('link-1')
            ->willThrowException($exception)
        ;

        $controller = new TrainerDexLinkController($service);

        $response = $controller->delete('link-1');

        $this->assertSame(404, $response->getStatusCode());
    }
}
