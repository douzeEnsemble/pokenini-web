<?php

declare(strict_types=1);

namespace App\Tests\Unit\Controller;

use App\Controller\AdminActionController;
use App\Service\Api\AdminActionService;
use App\Service\CacheInvalidatorService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * @internal
 */
#[CoversClass(AdminActionController::class)]
class AdminActionControllerTest extends TestCase
{
    public function testAction(): void
    {
        $cacheInvalidatorService = $this->createMock(CacheInvalidatorService::class);
        $cacheInvalidatorService
            ->expects($this->once())
            ->method('invalidate')
            ->with('something')
        ;

        $adminActionService = $this->createMock(AdminActionService::class);

        $session = $this->createMock(SessionInterface::class);
        $session
            ->expects($this->once())
            ->method('set')
        ;

        $requestStack = $this->createMock(RequestStack::class);
        $requestStack
            ->expects($this->once())
            ->method('getSession')
            ->willReturn($session)
        ;

        $logger = $this->createMock(LoggerInterface::class);

        $authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $authorizationChecker
            ->expects($this->once())
            ->method('isGranted')
            ->willReturn(true)
        ;

        $router = $this->createMock(Router::class);
        $router
            ->expects($this->once())
            ->method('generate')
            ->with(
                'app_admin_index',
                [
                    '_fragment' => 'invalidate_something',
                ]
            )
            ->willReturn('/admin')
        ;

        $container = $this->createMock(ContainerInterface::class);
        $container
            ->expects($this->once())
            ->method('has')
            ->willReturn(true)
        ;
        $container
            ->expects($this->exactly(2))
            ->method('get')
            ->willReturn($authorizationChecker, $router)
        ;

        $controller = new AdminActionController(
            $cacheInvalidatorService,
            $adminActionService,
            $requestStack,
            $logger
        );

        $controller->setContainer($container);

        $response = $controller->invalidate('something');

        $this->assertSame('/admin', $response->getTargetUrl());
    }

    public function testFailUpdateLogs(): void
    {
        $controller = $this->assertFailActionLogs('update');

        $controller->update('something');
    }

    public function testFailCalculateLogs(): void
    {
        $controller = $this->assertFailActionLogs('calculate');

        $controller->calculate('something');
    }

    private function assertFailActionLogs(string $action): AdminActionController
    {
        $cacheInvalidatorService = $this->createMock(CacheInvalidatorService::class);

        $adminActionService = $this->createMock(AdminActionService::class);
        $adminActionService
            ->expects($this->once())
            ->method($action)
            ->willThrowException(new \Exception('Aouch'))
        ;

        $session = $this->createMock(SessionInterface::class);
        $session
            ->expects($this->once())
            ->method('set')
        ;

        $requestStack = $this->createMock(RequestStack::class);
        $requestStack
            ->expects($this->once())
            ->method('getSession')
            ->willReturn($session)
        ;

        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects($this->once())
            ->method('critical')
            ->with(
                $this->equalTo('Aouch'),
                $this->equalTo([
                    'name' => 'something',
                    'action' => $action,
                ])
            )
        ;

        $authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $authorizationChecker
            ->expects($this->once())
            ->method('isGranted')
            ->willReturn(true)
        ;

        $router = $this->createMock(Router::class);
        $router
            ->expects($this->once())
            ->method('generate')
            ->willReturn('/admin')
        ;

        $container = $this->createMock(ContainerInterface::class);
        $container
            ->expects($this->once())
            ->method('has')
            ->willReturn(true)
        ;
        $container
            ->expects($this->exactly(2))
            ->method('get')
            ->willReturn($authorizationChecker, $router)
        ;

        $controller = new AdminActionController(
            $cacheInvalidatorService,
            $adminActionService,
            $requestStack,
            $logger
        );

        $controller->setContainer($container);

        return $controller;
    }
}
