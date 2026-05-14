<?php

declare(strict_types=1);

namespace App\Tests\Unit\Controller;

use App\Controller\AdminActionController;
use App\DTO\AdminAction;
use App\Event\AdminActionSucceededEvent;
use App\Service\Back\AdminActionService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\HttpClient\Exception\HttpExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

/**
 * @internal
 *
 * @SuppressWarnings("PHPMD.CouplingBetweenObjects")
 */
#[CoversClass(AdminActionController::class)]
final class AdminActionControllerTest extends TestCase
{
    public function testAction(): void
    {
        $adminActionService = $this->createMock(AdminActionService::class);
        $adminActionService
            ->expects($this->once())
            ->method('execute')
            ->with('invalidate', 'something')
            ->willReturn(new AdminAction('invalidate', 'something', 'ok', '', ''))
        ;

        $session = $this->createMock(SessionInterface::class);
        $session
            ->expects($this->once())
            ->method('set')
        ;

        $requestStack = $this->createMock(RequestStack::class);
        $requestStack
            ->expects($this->once())
            ->method('getSession')
            ->willReturn($session)
        ;

        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects($this->never())
            ->method('critical')
        ;

        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $eventDispatcher
            ->expects($this->once())
            ->method('dispatch')
            ->with($this->isInstanceOf(AdminActionSucceededEvent::class))
        ;

        $router = $this->createMock(RouterInterface::class);
        $router
            ->expects($this->once())
            ->method('generate')
            ->with(
                'app_admin_index',
                [
                    '_fragment' => 'invalidate_something',
                ]
            )
            ->willReturn('/admin')
        ;

        $container = $this->createMock(ContainerInterface::class);
        $container
            ->expects($this->once())
            ->method('get')
            ->willReturn($router)
        ;

        $controller = new AdminActionController(
            $adminActionService,
            $requestStack,
            $logger,
            $eventDispatcher,
        );
        $controller->setContainer($container);

        $response = $controller->invalidate('something');

        $this->assertSame('/admin', $response->getTargetUrl());
    }

    public function testFailUpdateLogs(): void
    {
        $controller = $this->assertFailActionLogs('update');

        $controller->update('something');
    }

    public function testFailCalculateLogs(): void
    {
        $controller = $this->assertFailActionLogs('calculate');

        $controller->calculate('something');
    }

    public function testTransportExceptionIsLogged(): void
    {
        $adminActionService = $this->createMock(AdminActionService::class);
        $adminActionService
            ->expects($this->once())
            ->method('execute')
            ->willThrowException($this->createStub(TransportExceptionInterface::class))
        ;

        $session = $this->createMock(SessionInterface::class);
        $session->expects($this->once())->method('set');

        $requestStack = $this->createMock(RequestStack::class);
        $requestStack->expects($this->once())->method('getSession')->willReturn($session);

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('critical');

        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $eventDispatcher->expects($this->never())->method('dispatch');

        $router = $this->createMock(RouterInterface::class);
        $router->expects($this->once())->method('generate')->willReturn('/admin');

        $container = $this->createMock(ContainerInterface::class);
        $container->expects($this->once())->method('get')->willReturn($router);

        $controller = new AdminActionController(
            $adminActionService,
            $requestStack,
            $logger,
            $eventDispatcher,
        );
        $controller->setContainer($container);

        $controller->update('something');
    }

    public function testLogicExceptionPropagates(): void
    {
        $adminActionService = $this->createMock(AdminActionService::class);
        $adminActionService
            ->expects($this->once())
            ->method('execute')
            ->willThrowException(new \LogicException('Bug'))
        ;

        $session = $this->createStub(SessionInterface::class);

        $requestStack = $this->createStub(RequestStack::class);
        $requestStack->method('getSession')->willReturn($session);

        $logger = $this->createStub(LoggerInterface::class);
        $eventDispatcher = $this->createStub(EventDispatcherInterface::class);

        $router = $this->createStub(RouterInterface::class);
        $router->method('generate')->willReturn('/admin');

        $container = $this->createStub(ContainerInterface::class);
        $container->method('get')->willReturn($router);

        $controller = new AdminActionController(
            $adminActionService,
            $requestStack,
            $logger,
            $eventDispatcher,
        );
        $controller->setContainer($container);

        $this->expectException(\LogicException::class);
        $controller->invalidate('something');
    }

    private function assertFailActionLogs(string $action): AdminActionController
    {
        $adminActionService = $this->createMock(AdminActionService::class);
        $adminActionService
            ->expects($this->once())
            ->method('execute')
            ->willThrowException($this->createStub(HttpExceptionInterface::class))
        ;

        $session = $this->createMock(SessionInterface::class);
        $session
            ->expects($this->once())
            ->method('set')
        ;

        $requestStack = $this->createMock(RequestStack::class);
        $requestStack
            ->expects($this->once())
            ->method('getSession')
            ->willReturn($session)
        ;

        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects($this->once())
            ->method('critical')
            ->with(
                $this->isString(),
                $this->equalTo([
                    'name' => 'something',
                    'action' => $action,
                ])
            )
        ;

        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $eventDispatcher
            ->expects($this->never())
            ->method('dispatch')
        ;

        $router = $this->createMock(RouterInterface::class);
        $router
            ->expects($this->once())
            ->method('generate')
            ->with(
                'app_admin_index',
                [
                    '_fragment' => $action.'_something',
                ]
            )
            ->willReturn('/admin')
        ;

        $container = $this->createMock(ContainerInterface::class);
        $container
            ->expects($this->once())
            ->method('get')
            ->willReturn($router)
        ;

        $controller = new AdminActionController(
            $adminActionService,
            $requestStack,
            $logger,
            $eventDispatcher,
        );

        $controller->setContainer($container);

        return $controller;
    }
}
