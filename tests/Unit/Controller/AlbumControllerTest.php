<?php

declare(strict_types=1);

namespace App\Tests\Unit\Controller;

use App\Controller\AlbumController;
use App\Security\UserTokenService;
use App\Service\ApiService;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Symfony\Component\HttpClient\Exception\TransportException;
use Symfony\Component\HttpFoundation\InputBag;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class AlbumControllerTest extends TestCase
{
    public function testUpsert(): void
    {
        $apiService = $this->createMock(ApiService::class);
        $apiService
            ->expects($this->once())
            ->method('modifyAlbum')
            ->with(
                'PATCH',
                'douze',
                'machi',
                '{}',
                '1234567890'
            )
        ;
        $apiService
            ->expects($this->once())
            ->method('invalidateCacheAlbums')
        ;

        $userTokenService = $this->createMock(UserTokenService::class);
        $userTokenService
            ->expects($this->once())
            ->method('getLoggedUserToken')
            ->willReturn('1234567890')
        ;

        $authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $authorizationChecker
            ->expects($this->once())
            ->method('isGranted')
            ->willReturn(true)
        ;

        $container = $this->createMock(ContainerInterface::class);
        $container
            ->expects($this->once())
            ->method('has')
            ->willReturn(true)
        ;
        $container
            ->expects($this->once())
            ->method('get')
            ->willReturn($authorizationChecker)
        ;

        $controller = new AlbumController(
            $apiService,
            $userTokenService
        );
        $controller->setContainer($container);

        $request = $this->createMock(Request::class);
        $request
            ->expects($this->once())
            ->method('getContent')
            ->willReturn('{}')
        ;
        $request
            ->expects($this->once())
            ->method('getMethod')
            ->willReturn('PATCH')
        ;

        $response = $controller->upsert('douze', 'machi', $request);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEmpty($response->getContent());
    }

    public function testUpsertBadContent(): void
    {
        $apiService = $this->createMock(ApiService::class);

        $userTokenService = $this->createMock(UserTokenService::class);
        $userTokenService
            ->expects($this->once())
            ->method('getLoggedUserToken')
            ->willReturn('1234567890')
        ;

        $authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $authorizationChecker
            ->expects($this->once())
            ->method('isGranted')
            ->willReturn(true)
        ;

        $container = $this->createMock(ContainerInterface::class);
        $container
            ->expects($this->once())
            ->method('has')
            ->willReturn(true)
        ;
        $container
            ->expects($this->once())
            ->method('get')
            ->willReturn($authorizationChecker)
        ;

        $controller = new AlbumController(
            $apiService,
            $userTokenService
        );
        $controller->setContainer($container);

        $request = $this->createMock(Request::class);
        $request
            ->expects($this->once())
            ->method('getContent')
            ->willReturn([])
        ;
        $request
            ->expects($this->once())
            ->method('getMethod')
            ->willReturn('PATCH')
        ;

        $this->expectError();
        $this->expectErrorMessage('Array to string conversion');

        $controller->upsert('douze', 'machi', $request);
    }

    public function testUpsertApiException(): void
    {
        $apiService = $this->createMock(ApiService::class);
        $apiService
            ->expects($this->once())
            ->method('modifyAlbum')
            ->willThrowException(
                new TransportException('Whoops!')
            )
            ->with(
                'PATCH',
                'douze',
                'machi',
                '{}',
                '1234567890'
            )
        ;

        $userTokenService = $this->createMock(UserTokenService::class);
        $userTokenService
            ->expects($this->once())
            ->method('getLoggedUserToken')
            ->willReturn('1234567890')
        ;

        $authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $authorizationChecker
            ->expects($this->once())
            ->method('isGranted')
            ->willReturn(true)
        ;

        $container = $this->createMock(ContainerInterface::class);
        $container
            ->expects($this->once())
            ->method('has')
            ->willReturn(true)
        ;
        $container
            ->expects($this->once())
            ->method('get')
            ->willReturn($authorizationChecker)
        ;

        $controller = new AlbumController(
            $apiService,
            $userTokenService
        );
        $controller->setContainer($container);

        $request = $this->createMock(Request::class);
        $request
            ->expects($this->once())
            ->method('getContent')
            ->willReturn('{}')
        ;
        $request
            ->expects($this->once())
            ->method('getMethod')
            ->willReturn('PATCH')
        ;

        $response = $controller->upsert('douze', 'machi', $request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(500, $response->getStatusCode());
        $this->assertEquals('{"error":"Whoops!"}', $response->getContent());
    }

    public function testIndexApiException(): void
    {
        $apiService = $this->createMock(ApiService::class);
        $apiService
            ->expects($this->once())
            ->method('getPokedex')
            ->willThrowException(
                new TransportException('Whoops!')
            )
            ->with(
                'douze',
                '121212',
            )
        ;

        $userTokenService = $this->createMock(UserTokenService::class);
        $userTokenService
            ->expects($this->once())
            ->method('getLoggedUserToken')
            ->willReturn('1234567890')
        ;

        $controller = new AlbumController(
            $apiService,
            $userTokenService
        );

        $request = $this->createMock(Request::class);
        $request->query = new InputBag(['t' => '121212']);

        $this->expectException(NotFoundHttpException::class);

        $controller->index($request, 'douze');
    }
}
