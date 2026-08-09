<?php

declare(strict_types=1);

namespace App\Tests\Unit\Controller;

use App\Controller\AdminCalculateDataController;
use App\DTO\AdminAction;
use App\Service\Back\GetActionLogsService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBag;
use Symfony\Component\HttpFoundation\Session\FlashBagAwareSessionInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Twig\Environment;

/**
 * @internal
 */
#[CoversClass(AdminCalculateDataController::class)]
final class AdminCalculateDataControllerTest extends TestCase
{
    public function testCalculateData(): void
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
                'Admin/calculate_data.html.twig',
                [
                    'actionLogsData' => [],
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

        $controller->calculateData($requestStack);

        $this->assertEquals(
            [
                'action' => ['truc'],
                'item' => ['machin'],
                'state' => ['ok'],
            ],
            $flashBag->all()
        );
    }

    public function testCalculateDataError(): void
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
                'Admin/calculate_data.html.twig',
                [
                    'actionLogsData' => [],
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

        $controller->calculateData($requestStack);

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

    private function getController(): AdminCalculateDataController
    {
        $getActionLogsService = $this->createMock(GetActionLogsService::class);
        $getActionLogsService
            ->expects($this->once())
            ->method('get')
            ->willReturn([])
        ;

        return new AdminCalculateDataController($getActionLogsService);
    }
}
