<?php

declare(strict_types=1);

namespace App\Tests\Unit\Controller;

use App\Controller\AdminTriggerPipelineController;
use App\DTO\AdminAction;
use App\Service\Back\GetActionLogsService;
use App\Service\Back\GetBannerPipelineStatusService;
use App\Service\Back\GetImagePipelineStatusService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBag;
use Symfony\Component\HttpFoundation\Session\FlashBagAwareSessionInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Twig\Environment;

/**
 * @internal
 */
#[CoversClass(AdminTriggerPipelineController::class)]
final class AdminTriggerPipelineControllerTest extends TestCase
{
    public function testTriggerPipeline(): void
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
                'Admin/trigger_pipeline.html.twig',
                [
                    'actionLogsData' => [],
                    'imagePipelineStatus' => null,
                    'bannerPipelineStatus' => null,
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

        $controller->triggerPipeline($requestStack, new Request());

        $this->assertEquals(
            [
                'action' => ['truc'],
                'item' => ['machin'],
                'state' => ['ok'],
            ],
            $flashBag->all()
        );
    }

    public function testTriggerPipelineError(): void
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
                'Admin/trigger_pipeline.html.twig',
                [
                    'actionLogsData' => [],
                    'imagePipelineStatus' => null,
                    'bannerPipelineStatus' => null,
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

        $controller->triggerPipeline($requestStack, new Request());

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

    public function testRefreshImagesDoesNotRefreshBanners(): void
    {
        $getImagePipelineStatusService = $this->createMock(GetImagePipelineStatusService::class);
        $getImagePipelineStatusService
            ->expects($this->once())
            ->method('get')
            ->with(true)
            ->willReturn(null)
        ;

        $getBannerPipelineStatusService = $this->createMock(GetBannerPipelineStatusService::class);
        $getBannerPipelineStatusService
            ->expects($this->once())
            ->method('get')
            ->with(false)
            ->willReturn(null)
        ;

        $controller = new AdminTriggerPipelineController(
            $this->createActionLogsServiceStub(),
            $getImagePipelineStatusService,
            $getBannerPipelineStatusService,
        );
        $requestStack = $this->createRequestStackStub();
        $controller->setContainer($this->createContainerStub($requestStack));

        $controller->triggerPipeline($requestStack, new Request(['refresh_images' => '123']));
    }

    public function testRefreshBannersDoesNotRefreshImages(): void
    {
        $getImagePipelineStatusService = $this->createMock(GetImagePipelineStatusService::class);
        $getImagePipelineStatusService
            ->expects($this->once())
            ->method('get')
            ->with(false)
            ->willReturn(null)
        ;

        $getBannerPipelineStatusService = $this->createMock(GetBannerPipelineStatusService::class);
        $getBannerPipelineStatusService
            ->expects($this->once())
            ->method('get')
            ->with(true)
            ->willReturn(null)
        ;

        $controller = new AdminTriggerPipelineController(
            $this->createActionLogsServiceStub(),
            $getImagePipelineStatusService,
            $getBannerPipelineStatusService,
        );
        $requestStack = $this->createRequestStackStub();
        $controller->setContainer($this->createContainerStub($requestStack));

        $controller->triggerPipeline($requestStack, new Request(['refresh_banners' => '123']));
    }

    private function createActionLogsServiceStub(): GetActionLogsService
    {
        $getActionLogsService = $this->createStub(GetActionLogsService::class);
        $getActionLogsService->method('get')->willReturn([]);

        return $getActionLogsService;
    }

    private function createRequestStackStub(): RequestStack
    {
        $session = $this->createStub(SessionInterface::class);
        $session->method('get')->willReturn(null);

        $flashBagSession = $this->createStub(FlashBagAwareSessionInterface::class);
        $flashBagSession->method('getFlashBag')->willReturn(new FlashBag());

        $requestStack = $this->createStub(RequestStack::class);
        $requestStack->method('getSession')->willReturn($session, $flashBagSession, $flashBagSession, $flashBagSession);

        return $requestStack;
    }

    private function createContainerStub(RequestStack $requestStack): ContainerInterface
    {
        $twig = $this->createStub(Environment::class);
        $twig->method('render')->willReturn('<html></html>');

        $container = $this->createStub(ContainerInterface::class);
        $container->method('has')->willReturn(true);
        $container->method('get')->willReturnCallback(
            static fn (string $id) => 'twig' === $id ? $twig : $requestStack
        );

        return $container;
    }

    private function getController(): AdminTriggerPipelineController
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

        $getBannerPipelineStatusService = $this->createMock(GetBannerPipelineStatusService::class);
        $getBannerPipelineStatusService
            ->expects($this->once())
            ->method('get')
            ->with(false)
            ->willReturn(null)
        ;

        return new AdminTriggerPipelineController(
            $getActionLogsService,
            $getImagePipelineStatusService,
            $getBannerPipelineStatusService,
        );
    }
}
