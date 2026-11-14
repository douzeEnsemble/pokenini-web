<?php

declare(strict_types=1);

namespace App\Tests\Unit\Controller;

use App\Controller\AdminDataPipelineController;
use App\DTO\PipelineStep;
use App\Service\ActionPipelineService;
use App\Service\Back\GetActionLogsService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Twig\Environment;

/**
 * @internal
 */
#[CoversClass(AdminDataPipelineController::class)]
final class AdminDataPipelineControllerTest extends TestCase
{
    public function testDataPipeline(): void
    {
        $steps = [new PipelineStep('update', 'labels', 'update_data', 'tags-fill')];

        $twig = $this->createMock(Environment::class);
        $twig
            ->expects($this->once())
            ->method('render')
            ->with(
                'Admin/data_pipeline.html.twig',
                [
                    'steps' => $steps,
                    'actionLogsData' => [],
                ]
            )
            ->willReturn('<html></html>')
        ;

        $container = $this->createStub(ContainerInterface::class);
        $container->method('has')->willReturn(true);
        $container->method('get')->willReturn($twig);

        $pipelineService = $this->createMock(ActionPipelineService::class);
        $pipelineService->expects($this->once())->method('steps')->willReturn($steps);

        $getActionLogsService = $this->createMock(GetActionLogsService::class);
        $getActionLogsService->expects($this->once())->method('get')->willReturn([]);

        $controller = new AdminDataPipelineController($pipelineService, $getActionLogsService);
        $controller->setContainer($container);

        $response = $controller->dataPipeline();

        $this->assertSame('<html></html>', $response->getContent());
    }
}
