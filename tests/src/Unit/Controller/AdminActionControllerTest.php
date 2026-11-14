<?php

declare(strict_types=1);

namespace App\Tests\Unit\Controller;

use App\Controller\AdminActionController;
use App\DTO\AdminAction;
use App\Event\AdminActionSucceededEvent;
use App\Service\Back\AdminActionService;
use App\Service\Back\GetActionCatalogService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBag;
use Symfony\Component\HttpFoundation\Session\FlashBagAwareSessionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\HttpClient\Exception\HttpExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * @internal
 *
 * @SuppressWarnings("PHPMD.CouplingBetweenObjects")
 */
#[CoversClass(AdminActionController::class)]
final class AdminActionControllerTest extends TestCase
{
    private FlashBag $flashBag;

    #[\Override]
    protected function setUp(): void
    {
        $this->flashBag = new FlashBag();
    }

    #[Test]
    #[DataProvider('successfulActions')]
    public function success(string $action, string $name, string $expectedRoute): void
    {
        $service = $this->createMock(AdminActionService::class);
        $service
            ->expects($this->once())
            ->method('execute')
            ->with($action, $name)
            ->willReturn(new AdminAction($action, $name, 'ok', '', ''))
        ;

        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $dispatcher
            ->expects($this->once())
            ->method('dispatch')
            ->with($this->isInstanceOf(AdminActionSucceededEvent::class))
        ;

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->never())->method('critical');

        $controller = $this->controller($service, $logger, $dispatcher, $expectedRoute, $action.'_'.$name);

        $response = $this->call($controller, $action, $name);

        $this->assertSame('/admin', $response->getTargetUrl());
        $this->assertSame(
            ['action' => [$action], 'item' => [$name], 'state' => ['ok']],
            $this->flashBag->all(),
        );
    }

    /**
     * @return iterable<string, array{string, string, string}>
     */
    public static function successfulActions(): iterable
    {
        yield 'invalidate' => ['invalidate', 'something', 'app_admin_invalidate_data'];

        yield 'invalidate reports' => ['invalidate', 'reports', 'app_admin_reports'];

        yield 'update data' => ['update', 'labels', 'app_admin_update_data'];

        yield 'update availabilities' => ['update', 'games_availabilities', 'app_admin_update_availabilities'];

        yield 'update shinies availabilities' => ['update', 'games_shinies_availabilities', 'app_admin_update_availabilities'];

        yield 'update collections availabilities' => ['update', 'collections_availabilities', 'app_admin_update_availabilities'];

        yield 'calculate' => ['calculate', 'dex_availabilities', 'app_admin_calculate_data'];

        yield 'trigger images' => ['trigger', 'update_images', 'app_admin_trigger_pipeline'];

        yield 'trigger banners' => ['trigger', 'update_banners', 'app_admin_trigger_pipeline'];
    }

    #[Test]
    #[DataProvider('failingActions')]
    public function httpFailureIsLoggedAndFlashed(string $action, string $name): void
    {
        $failure = $this->httpFailure(500, 'Boom');

        $service = $this->createMock(AdminActionService::class);
        $service->expects($this->once())->method('execute')->willThrowException($failure);

        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $dispatcher->expects($this->never())->method('dispatch');

        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects($this->once())
            ->method('critical')
            ->with($this->isString(), ['name' => $name, 'action' => $action])
        ;

        $controller = $this->controller($service, $logger, $dispatcher, $this->expectedRouteFor($action), $action.'_'.$name);

        $this->call($controller, $action, $name);

        $flashes = $this->flashBag->all();
        $this->assertSame([$action], $flashes['action']);
        $this->assertSame([$name], $flashes['item']);
        $this->assertSame(['ko'], $flashes['state']);
        $this->assertSame(['Boom'], $flashes['error']);
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function failingActions(): iterable
    {
        yield 'update' => ['update', 'something'];

        yield 'calculate' => ['calculate', 'something'];

        yield 'trigger' => ['trigger', 'update_images'];
    }

    #[Test]
    public function transportExceptionIsLogged(): void
    {
        $service = $this->createMock(AdminActionService::class);
        $service
            ->expects($this->once())
            ->method('execute')
            ->willThrowException($this->createStub(TransportExceptionInterface::class))
        ;

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('critical');

        $controller = $this->controller(
            $service,
            $logger,
            $this->dispatcherNeverCalled(),
            'app_admin_update_data',
            'update_something',
        );

        $this->call($controller, 'update', 'something');

        $this->assertSame(['ko'], $this->flashBag->get('state'));
    }

    #[Test]
    public function alreadyRunningIsFlashedWithoutCriticalLog(): void
    {
        $failure = $this->httpFailure(409, 'Conflict');

        $service = $this->createMock(AdminActionService::class);
        $service->expects($this->once())->method('execute')->willThrowException($failure);

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->never())->method('critical');

        $controller = $this->controller(
            $service,
            $logger,
            $this->dispatcherNeverCalled(),
            'app_admin_update_data',
            'update_labels',
        );

        $this->call($controller, 'update', 'labels');

        $this->assertSame(['admin.action.already_running'], $this->flashBag->get('error'));
        $this->assertSame(['ko'], $this->flashBag->get('state'));
    }

    #[Test]
    public function logicExceptionPropagates(): void
    {
        $service = $this->createMock(AdminActionService::class);
        $service->expects($this->once())->method('execute')->willThrowException(new \LogicException('Bug'));

        $controller = $this->controller(
            $service,
            $this->createStub(LoggerInterface::class),
            $this->createStub(EventDispatcherInterface::class),
            'unused',
            'unused',
            routerCalled: false,
        );

        $this->expectException(\LogicException::class);
        $this->call($controller, 'invalidate', 'something');
    }

    #[Test]
    #[DataProvider('invalidTokenActions')]
    public function invalidCsrfToken(string $action, string $name): void
    {
        $service = $this->createMock(AdminActionService::class);
        $service->expects($this->never())->method('execute');

        $controller = $this->controller(
            $service,
            $this->createStub(LoggerInterface::class),
            $this->createStub(EventDispatcherInterface::class),
            'unused',
            'unused',
            tokenValid: false,
            routerCalled: false,
        );

        $this->expectException(AccessDeniedException::class);
        $this->call($controller, $action, $name);
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function invalidTokenActions(): iterable
    {
        yield 'update' => ['update', 'labels'];

        yield 'calculate' => ['calculate', 'dex_availabilities'];

        yield 'invalidate' => ['invalidate', 'dex'];

        yield 'trigger' => ['trigger', 'update_images'];
    }

    #[Test]
    #[DataProvider('unknownActions')]
    public function unknownActionIsNotFound(string $action): void
    {
        $service = $this->createMock(AdminActionService::class);
        $service->expects($this->never())->method('execute');

        $catalog = $this->createMock(GetActionCatalogService::class);
        $catalog->expects($this->once())->method('has')->with($action, 'truc')->willReturn(false);

        $controller = new AdminActionController(
            $service,
            $this->createStub(LoggerInterface::class),
            $this->createStub(EventDispatcherInterface::class),
            $catalog,
        );
        $controller->setContainer($this->createStub(ContainerInterface::class));

        $this->expectException(NotFoundHttpException::class);

        $this->call($controller, $action, 'truc');
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function unknownActions(): iterable
    {
        yield 'update' => ['update'];

        yield 'calculate' => ['calculate'];
    }

    private function call(AdminActionController $controller, string $action, string $name): RedirectResponse
    {
        $request = new Request([], ['_token' => 'valid_token']);

        return match ($action) {
            'update' => $controller->update($name, $request),
            'calculate' => $controller->calculate($name, $request),
            'trigger' => $controller->trigger($name, $request),
            default => $controller->invalidate($name, $request),
        };
    }

    private function controller(
        AdminActionService&MockObject $service,
        LoggerInterface $logger,
        EventDispatcherInterface $dispatcher,
        string $expectedRoute,
        string $expectedFragment,
        bool $tokenValid = true,
        bool $routerCalled = true,
    ): AdminActionController {
        $this->flashBag = new FlashBag();

        $session = $this->createStub(FlashBagAwareSessionInterface::class);
        $session->method('getFlashBag')->willReturn($this->flashBag);

        $requestStack = $this->createStub(RequestStack::class);
        $requestStack->method('getSession')->willReturn($session);

        $csrfManager = $this->createStub(CsrfTokenManagerInterface::class);
        $csrfManager->method('isTokenValid')->willReturn($tokenValid);

        $router = $this->createMock(RouterInterface::class);
        $router
            ->expects($routerCalled ? $this->once() : $this->never())
            ->method('generate')
            ->with($expectedRoute, ['_fragment' => $expectedFragment])
            ->willReturn('/admin')
        ;

        $container = $this->createStub(ContainerInterface::class);
        $container->method('has')->willReturn(true);
        $container->method('get')->willReturnMap([
            ['security.csrf.token_manager', $csrfManager],
            ['router', $router],
            ['request_stack', $requestStack],
        ]);

        $catalog = $this->createStub(GetActionCatalogService::class);
        $catalog->method('has')->willReturn(true);

        $controller = new AdminActionController($service, $logger, $dispatcher, $catalog);
        $controller->setContainer($container);

        return $controller;
    }

    private function dispatcherNeverCalled(): EventDispatcherInterface
    {
        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $dispatcher->expects($this->never())->method('dispatch');

        return $dispatcher;
    }

    private function httpFailure(int $status, string $message): \Throwable
    {
        $response = $this->responseWithStatus($status);

        return new class($message, $response) extends \RuntimeException implements HttpExceptionInterface {
            public function __construct(string $message, private readonly ResponseInterface $response)
            {
                parent::__construct($message);
            }

            #[\Override]
            public function getResponse(): ResponseInterface
            {
                return $this->response;
            }
        };
    }

    private function responseWithStatus(int $status): ResponseInterface
    {
        $response = $this->createStub(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn($status);

        return $response;
    }

    private function expectedRouteFor(string $action): string
    {
        return match ($action) {
            'update' => 'app_admin_update_data',
            'calculate' => 'app_admin_calculate_data',
            default => 'app_admin_trigger_pipeline',
        };
    }
}
