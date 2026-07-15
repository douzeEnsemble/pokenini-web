<?php

declare(strict_types=1);

namespace App\Tests\Unit\Controller;

use App\Controller\AdminController;
use App\DTO\AdminAction;
use App\Service\Back\GetActionLogsService;
use App\Service\Back\GetImagePipelineStatusService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBag;
use Symfony\Component\HttpFoundation\Session\FlashBagAwareSessionInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\RouterInterface;
use Twig\Environment;

/**
 * @internal
 */
#[CoversClass(AdminController::class)]
final class AdminControllerTest extends TestCase
{
    public function testIndexRedirectsToActions(): void
    {
        $router = $this->createMock(RouterInterface::class);
        $router
            ->expects($this->once())
            ->method('generate')
            ->with('app_admin_actions', [])
            ->willReturn('/fr/istration/actions')
        ;

        $container = $this->createMock(ContainerInterface::class);
        $container
            ->expects($this->once())
            ->method('get')
            ->with('router')
            ->willReturn($router)
        ;

        $controller = new AdminController(
            $this->createStub(GetActionLogsService::class),
            $this->createStub(GetImagePipelineStatusService::class),
        );
        $controller->setContainer($container);

        $response = $controller->index();

        $this->assertSame('/fr/istration/actions', $response->getTargetUrl());
        $this->assertSame(302, $response->getStatusCode());
    }

    public function testAdminAction(): void
    {
        $adminAction = new AdminAction(
            'truc',
            'machin',
            'ok',
            'content',
            '',
        );

        $flashBag = new FlashBag();

        $session = $this->createMock(SessionInterface::class);
        $session
            ->expects($this->once())
            ->method('get')
            ->with('admin.action.response.content')
            ->willReturn($adminAction)
        ;
        $session
            ->expects($this->once())
            ->method('remove')
            ->with('admin.action.response.content')
        ;

        $flashBagSession = $this->createMock(FlashBagAwareSessionInterface::class);
        $flashBagSession
            ->expects($this->exactly(3))
            ->method('getFlashBag')
            ->willReturn($flashBag)
        ;

        $requestStack = $this->createMock(RequestStack::class);
        $requestStack
            ->expects($this->exactly(4))
            ->method('getSession')
            ->willReturnOnConsecutiveCalls(
                $session,
                $flashBagSession,
                $flashBagSession,
                $flashBagSession,
            )
        ;

        $twig = $this->createMock(Environment::class);
        $twig
            ->expects($this->once())
            ->method('render')
            ->with(
                'Admin/actions.html.twig',
                [
                    'actionLogsData' => [],
                    'imagePipelineStatus' => null,
                ]
            )
            ->willReturn('<html></html>')
        ;

        $container = $this->createMock(ContainerInterface::class);
        $container
            ->expects($this->exactly(4))
            ->method('get')
            ->willReturnOnConsecutiveCalls(
                $requestStack,
                $requestStack,
                $requestStack,
                $twig,
            )
        ;
        $container
            ->expects($this->once())
            ->method('has')
            ->with('twig')
            ->willReturn(true)
        ;

        $controller = $this->getController();
        $controller->setContainer($container);

        $controller->actions($requestStack, new Request());

        $this->assertEquals(
            [
                'action' => ['truc'],
                'item' => ['machin'],
                'state' => ['ok'],
            ],
            $flashBag->all()
        );
    }

    public function testAdminActionError(): void
    {
        $adminAction = new AdminAction(
            'truc',
            'machin',
            'ok',
            'content',
            'error',
        );

        $flashBag = new FlashBag();

        $session = $this->createMock(SessionInterface::class);
        $session
            ->expects($this->once())
            ->method('get')
            ->with('admin.action.response.content')
            ->willReturn($adminAction)
        ;
        $session
            ->expects($this->once())
            ->method('remove')
            ->with('admin.action.response.content')
        ;

        $flashBagSession = $this->createMock(FlashBagAwareSessionInterface::class);
        $flashBagSession
            ->expects($this->exactly(4))
            ->method('getFlashBag')
            ->willReturn($flashBag)
        ;

        $requestStack = $this->createMock(RequestStack::class);
        $requestStack
            ->expects($this->exactly(5))
            ->method('getSession')
            ->willReturnOnConsecutiveCalls(
                $session,
                $flashBagSession,
                $flashBagSession,
                $flashBagSession,
                $flashBagSession,
            )
        ;

        $twig = $this->createMock(Environment::class);
        $twig
            ->expects($this->once())
            ->method('render')
            ->with(
                'Admin/actions.html.twig',
                [
                    'actionLogsData' => [],
                    'imagePipelineStatus' => null,
                ]
            )
            ->willReturn('<html></html>')
        ;

        $container = $this->createMock(ContainerInterface::class);
        $container
            ->expects($this->exactly(5))
            ->method('get')
            ->willReturnOnConsecutiveCalls(
                $requestStack,
                $requestStack,
                $requestStack,
                $requestStack,
                $twig,
            )
        ;
        $container
            ->expects($this->once())
            ->method('has')
            ->with('twig')
            ->willReturn(true)
        ;

        $controller = $this->getController();
        $controller->setContainer($container);

        $controller->actions($requestStack, new Request());

        $this->assertEquals(
            [
                'action' => ['truc'],
                'item' => ['machin'],
                'state' => ['ok'],
                'error' => ['error'],
            ],
            $flashBag->all()
        );
    }

    private function getController(): AdminController
    {
        $getActionLogsService = $this->createMock(GetActionLogsService::class);
        $getActionLogsService
            ->expects($this->once())
            ->method('get')
            ->willReturn([])
        ;

        $getImagePipelineStatusService = $this->createMock(GetImagePipelineStatusService::class);
        $getImagePipelineStatusService
            ->expects($this->once())
            ->method('get')
            ->with(false)
            ->willReturn(null)
        ;

        return new AdminController($getActionLogsService, $getImagePipelineStatusService);
    }
}
