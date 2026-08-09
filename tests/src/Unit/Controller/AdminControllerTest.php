<?php

declare(strict_types=1);

namespace App\Tests\Unit\Controller;

use App\Controller\AdminController;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\RouterInterface;

/**
 * @internal
 */
#[CoversClass(AdminController::class)]
final class AdminControllerTest extends TestCase
{
    #[Test]
    public function indexRedirectsToUpdateData(): void
    {
        $router = $this->createMock(RouterInterface::class);
        $router
            ->expects($this->once())
            ->method('generate')
            ->with('app_admin_update_data', [])
            ->willReturn('/fr/istration/update_data')
        ;

        $container = $this->createMock(ContainerInterface::class);
        $container
            ->expects($this->once())
            ->method('get')
            ->with('router')
            ->willReturn($router)
        ;

        $controller = new AdminController();
        $controller->setContainer($container);

        $response = $controller->index();

        $this->assertSame('/fr/istration/update_data', $response->getTargetUrl());
        $this->assertSame(302, $response->getStatusCode());
    }
}
