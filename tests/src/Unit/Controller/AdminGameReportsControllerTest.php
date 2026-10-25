<?php

declare(strict_types=1);

namespace App\Tests\Unit\Controller;

use App\Controller\AdminGameReportsController;
use App\Service\Back\GetGameReportsService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Twig\Environment;

/**
 * @internal
 */
#[CoversClass(AdminGameReportsController::class)]
final class AdminGameReportsControllerTest extends TestCase
{
    #[Test]
    public function gameReports(): void
    {
        $data = ['games' => [], 'daily_games' => []];

        $service = $this->createMock(GetGameReportsService::class);
        $service
            ->expects($this->once())
            ->method('get')
            ->willReturn($data)
        ;

        $twig = $this->createMock(Environment::class);
        $twig
            ->expects($this->once())
            ->method('render')
            ->with(
                'Admin/game_reports.html.twig',
                [
                    'gameReportsData' => $data,
                ]
            )
            ->willReturn('<html></html>')
        ;

        $container = $this->createMock(ContainerInterface::class);
        $container
            ->expects($this->once())
            ->method('get')
            ->willReturn($twig)
        ;
        $container
            ->expects($this->once())
            ->method('has')
            ->with('twig')
            ->willReturn(true)
        ;

        $controller = new AdminGameReportsController($service);
        $controller->setContainer($container);

        $response = $controller->gameReports();

        $this->assertSame('<html></html>', $response->getContent());
    }
}
