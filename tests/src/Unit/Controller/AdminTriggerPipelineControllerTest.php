<?php

declare(strict_types=1);

namespace App\Tests\Unit\Controller;

use App\Controller\AdminTriggerPipelineController;
use App\Service\Back\GetActionLogsService;
use App\Service\Back\GetBannerPipelineStatusService;
use App\Service\Back\GetImagePipelineStatusService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Twig\Environment;

/**
 * @internal
 */
#[CoversClass(AdminTriggerPipelineController::class)]
final class AdminTriggerPipelineControllerTest extends TestCase
{
    public function testTriggerPipeline(): void
    {
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

        $container = $this->createStub(ContainerInterface::class);
        $container->method('has')->willReturn(true);
        $container->method('get')->willReturn($twig);

        $controller = $this->getController();
        $controller->setContainer($container);

        $response = $controller->triggerPipeline(new Request());

        $this->assertSame('<html></html>', $response->getContent());
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
        $controller->setContainer($this->createContainerStub());

        $controller->triggerPipeline(new Request(['refresh_images' => '123']));
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
        $controller->setContainer($this->createContainerStub());

        $controller->triggerPipeline(new Request(['refresh_banners' => '123']));
    }

    private function createActionLogsServiceStub(): GetActionLogsService
    {
        $getActionLogsService = $this->createStub(GetActionLogsService::class);
        $getActionLogsService->method('get')->willReturn([]);

        return $getActionLogsService;
    }

    private function createContainerStub(): ContainerInterface
    {
        $twig = $this->createStub(Environment::class);
        $twig->method('render')->willReturn('<html></html>');

        $container = $this->createStub(ContainerInterface::class);
        $container->method('has')->willReturn(true);
        $container->method('get')->willReturn($twig);

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
