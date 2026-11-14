<?php

declare(strict_types=1);

namespace App\Tests\Unit\Controller;

use App\Controller\AdminInvalidateDataController;
use App\Service\Back\GetActionLogsService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Twig\Environment;

/**
 * @internal
 */
#[CoversClass(AdminInvalidateDataController::class)]
final class AdminInvalidateDataControllerTest extends TestCase
{
    public function testInvalidateData(): void
    {
        $twig = $this->createMock(Environment::class);
        $twig
            ->expects($this->once())
            ->method('render')
            ->with(
                'Admin/invalidate_data.html.twig',
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

        $controller = new AdminInvalidateDataController($getActionLogsService);
        $controller->setContainer($container);

        $response = $controller->invalidateData();

        $this->assertSame('<html></html>', $response->getContent());
    }
}
