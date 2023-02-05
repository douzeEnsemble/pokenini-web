<?php

declare(strict_types=1);

namespace App\Tests\Unit\Controller;

use App\Controller\AdminActionController;
use App\Service\ApiService;
use App\Service\CacheInvalidatorService;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class AdminActionControllerTest extends TestCase
{
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

    public function testInvalidateCall(): void
    {
        $cacheInvalidatorService = $this->createMock(CacheInvalidatorService::class);
        $cacheInvalidatorService
            ->expects($this->once())
            ->method('invalidate')
            ->with('something')
        ;

        $apiService = $this->createMock(ApiService::class);

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
            $apiService,
            $requestStack,
            $logger
        );

        $controller->setContainer($container);

        $controller->invalidate('something');
    }

    private function assertFailActionLogs(string $action): AdminActionController
    {
        $cacheInvalidatorService = $this->createMock(CacheInvalidatorService::class);

        $apiService = $this->createMock(ApiService::class);
        $apiService
            ->expects($this->once())
            ->method('admin' . ucfirst($action))
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
            $apiService,
            $requestStack,
            $logger
        );

        $controller->setContainer($container);

        return $controller;
    }
}
