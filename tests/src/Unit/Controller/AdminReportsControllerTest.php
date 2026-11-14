<?php

declare(strict_types=1);

namespace App\Tests\Unit\Controller;

use App\Controller\AdminReportsController;
use App\Service\Back\GetGameReportsService;
use App\Service\Back\GetReportsService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Twig\Environment;

/**
 * @internal
 */
#[CoversClass(AdminReportsController::class)]
final class AdminReportsControllerTest extends TestCase
{
    #[Test]
    public function reports(): void
    {
        $twig = $this->createMock(Environment::class);
        $twig
            ->expects($this->once())
            ->method('render')
            ->with(
                'Admin/reports.html.twig',
                [
                    'reportsData' => [],
                    'gameReportsData' => ['games' => []],
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

        $getReportsService = $this->createMock(GetReportsService::class);
        $getReportsService
            ->expects($this->once())
            ->method('get')
            ->willReturn([])
        ;

        $getGameReportsService = $this->createMock(GetGameReportsService::class);
        $getGameReportsService
            ->expects($this->once())
            ->method('get')
            ->willReturn(['games' => []])
        ;

        $controller = new AdminReportsController($getReportsService, $getGameReportsService);
        $controller->setContainer($container);

        $response = $controller->reports();

        $this->assertSame('<html></html>', $response->getContent());
    }
}
