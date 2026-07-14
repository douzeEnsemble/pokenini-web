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
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
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
            ->with('app_admin_actions', ['_fragment' => 'invalidate_something'])
            ->willReturn('/admin')
        ;

        $csrfManager = $this->createStub(CsrfTokenManagerInterface::class);
        $csrfManager->method('isTokenValid')->willReturn(true);

        $container = $this->createMock(ContainerInterface::class);
        $container
            ->expects($this->once())
            ->method('has')
            ->with('security.csrf.token_manager')
            ->willReturn(true)
        ;
        $container
            ->expects($this->exactly(2))
            ->method('get')
            ->willReturnMap([
                ['security.csrf.token_manager', $csrfManager],
                ['router', $router],
            ])
        ;

        $controller = new AdminActionController(
            $adminActionService,
            $requestStack,
            $logger,
            $eventDispatcher,
        );
        $controller->setContainer($container);

        $response = $controller->invalidate('something', new Request([], ['_token' => 'valid_token']));

        $this->assertSame('/admin', $response->getTargetUrl());
    }

    public function testFailUpdateLogs(): void
    {
        $controller = $this->assertFailActionLogs('update');

        $controller->update('something', new Request([], ['_token' => 'valid_token']));
    }

    public function testFailCalculateLogs(): void
    {
        $controller = $this->assertFailActionLogs('calculate');

        $controller->calculate('something', new Request([], ['_token' => 'valid_token']));
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
            ->willReturn('/admin')
        ;

        $csrfManager = $this->createStub(CsrfTokenManagerInterface::class);
        $csrfManager->method('isTokenValid')->willReturn(true);

        $container = $this->createMock(ContainerInterface::class);
        $container
            ->expects($this->once())
            ->method('has')
            ->with('security.csrf.token_manager')
            ->willReturn(true)
        ;
        $container
            ->expects($this->exactly(2))
            ->method('get')
            ->willReturnMap([
                ['security.csrf.token_manager', $csrfManager],
                ['router', $router],
            ])
        ;

        $controller = new AdminActionController(
            $adminActionService,
            $requestStack,
            $logger,
            $eventDispatcher,
        );
        $controller->setContainer($container);

        $controller->update('something', new Request([], ['_token' => 'valid_token']));
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

        $csrfManager = $this->createStub(CsrfTokenManagerInterface::class);
        $csrfManager->method('isTokenValid')->willReturn(true);

        $container = $this->createMock(ContainerInterface::class);
        $container
            ->expects($this->once())
            ->method('has')
            ->willReturn(true)
        ;
        $container
            ->expects($this->once())
            ->method('get')
            ->willReturnMap([
                ['security.csrf.token_manager', $csrfManager],
            ])
        ;

        $controller = new AdminActionController(
            $adminActionService,
            $requestStack,
            $logger,
            $eventDispatcher,
        );
        $controller->setContainer($container);

        $this->expectException(\LogicException::class);
        $controller->invalidate('something', new Request([], ['_token' => 'valid_token']));
    }

    public function testUpdateInvalidCsrfToken(): void
    {
        $csrfManager = $this->createMock(CsrfTokenManagerInterface::class);
        $csrfManager
            ->expects($this->once())
            ->method('isTokenValid')
            ->willReturn(false)
        ;

        $container = $this->createMock(ContainerInterface::class);
        $container
            ->expects($this->once())
            ->method('has')
            ->with('security.csrf.token_manager')
            ->willReturn(true)
        ;
        $container
            ->expects($this->once())
            ->method('get')
            ->willReturnMap([['security.csrf.token_manager', $csrfManager]])
        ;

        $adminActionService = $this->createMock(AdminActionService::class);
        $adminActionService
            ->expects($this->never())
            ->method('execute')
        ;

        $controller = new AdminActionController(
            $adminActionService,
            $this->createStub(RequestStack::class),
            $this->createStub(LoggerInterface::class),
            $this->createStub(EventDispatcherInterface::class),
        );
        $controller->setContainer($container);

        $this->expectException(AccessDeniedException::class);
        $controller->update('labels', new Request([], ['_token' => 'bad_token']));
    }

    public function testCalculateInvalidCsrfToken(): void
    {
        $csrfManager = $this->createMock(CsrfTokenManagerInterface::class);
        $csrfManager
            ->expects($this->once())
            ->method('isTokenValid')
            ->willReturn(false)
        ;

        $container = $this->createMock(ContainerInterface::class);
        $container
            ->expects($this->once())
            ->method('has')
            ->with('security.csrf.token_manager')
            ->willReturn(true)
        ;
        $container
            ->expects($this->once())
            ->method('get')
            ->willReturnMap([['security.csrf.token_manager', $csrfManager]])
        ;

        $adminActionService = $this->createMock(AdminActionService::class);
        $adminActionService
            ->expects($this->never())
            ->method('execute')
        ;

        $controller = new AdminActionController(
            $adminActionService,
            $this->createStub(RequestStack::class),
            $this->createStub(LoggerInterface::class),
            $this->createStub(EventDispatcherInterface::class),
        );
        $controller->setContainer($container);

        $this->expectException(AccessDeniedException::class);
        $controller->calculate('game_bundles_availabilities', new Request([], ['_token' => 'bad_token']));
    }

    public function testInvalidateInvalidCsrfToken(): void
    {
        $csrfManager = $this->createMock(CsrfTokenManagerInterface::class);
        $csrfManager
            ->expects($this->once())
            ->method('isTokenValid')
            ->willReturn(false)
        ;

        $container = $this->createMock(ContainerInterface::class);
        $container
            ->expects($this->once())
            ->method('has')
            ->with('security.csrf.token_manager')
            ->willReturn(true)
        ;
        $container
            ->expects($this->once())
            ->method('get')
            ->willReturnMap([['security.csrf.token_manager', $csrfManager]])
        ;

        $adminActionService = $this->createMock(AdminActionService::class);
        $adminActionService
            ->expects($this->never())
            ->method('execute')
        ;

        $controller = new AdminActionController(
            $adminActionService,
            $this->createStub(RequestStack::class),
            $this->createStub(LoggerInterface::class),
            $this->createStub(EventDispatcherInterface::class),
        );
        $controller->setContainer($container);

        $this->expectException(AccessDeniedException::class);
        $controller->invalidate('labels', new Request([], ['_token' => 'bad_token']));
    }

    public function testInvalidateReportsRedirectsToReportsPage(): void
    {
        $adminActionService = $this->createMock(AdminActionService::class);
        $adminActionService
            ->expects($this->once())
            ->method('execute')
            ->with('invalidate', 'reports')
            ->willReturn(new AdminAction('invalidate', 'reports', 'ok', '', ''))
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
            ->with('app_admin_reports', ['_fragment' => 'invalidate_reports'])
            ->willReturn('/admin')
        ;

        $csrfManager = $this->createStub(CsrfTokenManagerInterface::class);
        $csrfManager->method('isTokenValid')->willReturn(true);

        $container = $this->createMock(ContainerInterface::class);
        $container
            ->expects($this->once())
            ->method('has')
            ->with('security.csrf.token_manager')
            ->willReturn(true)
        ;
        $container
            ->expects($this->exactly(2))
            ->method('get')
            ->willReturnMap([
                ['security.csrf.token_manager', $csrfManager],
                ['router', $router],
            ])
        ;

        $controller = new AdminActionController(
            $adminActionService,
            $requestStack,
            $logger,
            $eventDispatcher,
        );
        $controller->setContainer($container);

        $response = $controller->invalidate('reports', new Request([], ['_token' => 'valid_token']));

        $this->assertSame('/admin', $response->getTargetUrl());
    }

    public function testTriggerAction(): void
    {
        $adminActionService = $this->createMock(AdminActionService::class);
        $adminActionService
            ->expects($this->once())
            ->method('execute')
            ->with('trigger', 'update_images')
            ->willReturn(new AdminAction('trigger', 'update_images', 'ok', '', ''))
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
            ->with('app_admin_actions', ['_fragment' => 'trigger_update_images'])
            ->willReturn('/admin')
        ;

        $csrfManager = $this->createStub(CsrfTokenManagerInterface::class);
        $csrfManager->method('isTokenValid')->willReturn(true);

        $container = $this->createMock(ContainerInterface::class);
        $container
            ->expects($this->once())
            ->method('has')
            ->with('security.csrf.token_manager')
            ->willReturn(true)
        ;
        $container
            ->expects($this->exactly(2))
            ->method('get')
            ->willReturnMap([
                ['security.csrf.token_manager', $csrfManager],
                ['router', $router],
            ])
        ;

        $controller = new AdminActionController(
            $adminActionService,
            $requestStack,
            $logger,
            $eventDispatcher,
        );
        $controller->setContainer($container);

        $response = $controller->trigger('update_images', new Request([], ['_token' => 'valid_token']));

        $this->assertSame('/admin', $response->getTargetUrl());
    }

    public function testFailTriggerLogs(): void
    {
        $controller = $this->assertFailActionLogs('trigger', 'update_images');

        $controller->trigger('update_images', new Request([], ['_token' => 'valid_token']));
    }

    public function testTriggerInvalidCsrfToken(): void
    {
        $csrfManager = $this->createMock(CsrfTokenManagerInterface::class);
        $csrfManager
            ->expects($this->once())
            ->method('isTokenValid')
            ->willReturn(false)
        ;

        $container = $this->createMock(ContainerInterface::class);
        $container
            ->expects($this->once())
            ->method('has')
            ->with('security.csrf.token_manager')
            ->willReturn(true)
        ;
        $container
            ->expects($this->once())
            ->method('get')
            ->willReturnMap([['security.csrf.token_manager', $csrfManager]])
        ;

        $adminActionService = $this->createMock(AdminActionService::class);
        $adminActionService
            ->expects($this->never())
            ->method('execute')
        ;

        $controller = new AdminActionController(
            $adminActionService,
            $this->createStub(RequestStack::class),
            $this->createStub(LoggerInterface::class),
            $this->createStub(EventDispatcherInterface::class),
        );
        $controller->setContainer($container);

        $this->expectException(AccessDeniedException::class);
        $controller->trigger('update_images', new Request([], ['_token' => 'bad_token']));
    }

    private function assertFailActionLogs(string $action, string $name = 'something'): AdminActionController
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
                    'name' => $name,
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
            ->with('app_admin_actions', ['_fragment' => $action.'_'.$name])
            ->willReturn('/admin')
        ;

        $csrfManager = $this->createStub(CsrfTokenManagerInterface::class);
        $csrfManager->method('isTokenValid')->willReturn(true);

        $container = $this->createMock(ContainerInterface::class);
        $container
            ->expects($this->once())
            ->method('has')
            ->with('security.csrf.token_manager')
            ->willReturn(true)
        ;
        $container
            ->expects($this->exactly(2))
            ->method('get')
            ->willReturnMap([
                ['security.csrf.token_manager', $csrfManager],
                ['router', $router],
            ])
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
