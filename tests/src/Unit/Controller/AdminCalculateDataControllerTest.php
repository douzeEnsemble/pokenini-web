<?php

declare(strict_types=1);

namespace App\Tests\Unit\Controller;

use App\Controller\AdminCalculateDataController;
use App\Service\Back\GetActionLogsService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Twig\Environment;

/**
 * @internal
 */
#[CoversClass(AdminCalculateDataController::class)]
final class AdminCalculateDataControllerTest extends TestCase
{
    public function testCalculateData(): void
    {
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
            ->expects($this->once())
            ->method('get')
            ->with('twig')
            ->willReturn($twig)
        ;
        $container
            ->expects($this->once())
            ->method('has')
            ->with('twig')
            ->willReturn(true)
        ;

        $getActionLogsService = $this->createMock(GetActionLogsService::class);
        $getActionLogsService
            ->expects($this->once())
            ->method('get')
            ->willReturn([])
        ;

        $controller = new AdminCalculateDataController($getActionLogsService);
        $controller->setContainer($container);

        $response = $controller->calculateData();

        $this->assertSame('<html></html>', $response->getContent());
    }
}
