<?php

declare(strict_types=1);

namespace App\Tests\Unit\Controller;

use App\Controller\AlbumController;
use App\Security\UserTokenService;
use App\Service\ApiService;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Symfony\Component\HttpClient\Exception\TransportException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class AlbumControllerUpsertTest extends TestCase
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

        $validator = $this->createMock(ValidatorInterface::class);
        $validator
            ->expects($this->once())
            ->method('validate')
            ->willReturn(
                new ConstraintViolationList([])
            )
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
            $userTokenService,
            $validator,
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

        $authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $authorizationChecker
            ->expects($this->once())
            ->method('isGranted')
            ->willReturn(true)
        ;

        $validator = $this->createMock(ValidatorInterface::class);

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
            $userTokenService,
            $validator,
        );
        $controller->setContainer($container);

        $request = $this->createMock(Request::class);
        $request
            ->expects($this->once())
            ->method('getContent')
            ->willReturn([])
        ;

        $response = $controller->upsert('douze', 'machi', $request);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(400, $response->getStatusCode());
        $this->assertEquals(
            '{"error":"Content must be a non-empty string"}',
            $response->getContent()
        );
    }

    public function testUpsertEmptyContent(): void
    {
        $apiService = $this->createMock(ApiService::class);

        $userTokenService = $this->createMock(UserTokenService::class);

        $authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $authorizationChecker
            ->expects($this->once())
            ->method('isGranted')
            ->willReturn(true)
        ;

        $validator = $this->createMock(ValidatorInterface::class);

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
            $userTokenService,
            $validator,
        );
        $controller->setContainer($container);

        $request = $this->createMock(Request::class);
        $request
            ->expects($this->once())
            ->method('getContent')
            ->willReturn('')
        ;

        $response = $controller->upsert('douze', 'machi', $request);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(400, $response->getStatusCode());
        $this->assertEquals(
            '{"error":"Content must be a non-empty string"}',
            $response->getContent()
        );
    }

    public function testUpsertInvalidContent(): void
    {
        $apiService = $this->createMock(ApiService::class);

        $userTokenService = $this->createMock(UserTokenService::class);

        $authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $authorizationChecker
            ->expects($this->once())
            ->method('isGranted')
            ->willReturn(true)
        ;

        $validator = $this->createMock(ValidatorInterface::class);
        $validator
            ->expects($this->once())
            ->method('validate')
            ->willReturn(
                new ConstraintViolationList([
                    new ConstraintViolation(
                        'Alors en fait, non',
                        null,
                        [],
                        'douze',
                        null,
                        'what?'
                    ),
                ])
            )
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
            $userTokenService,
            $validator,
        );
        $controller->setContainer($container);

        $request = $this->createMock(Request::class);
        $request
            ->expects($this->once())
            ->method('getContent')
            ->willReturn('something')
        ;

        $response = $controller->upsert('douze', 'machi', $request);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(400, $response->getStatusCode());
        $this->assertEquals(
            '{"error":"Alors en fait, non"}',
            $response->getContent()
        );
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

        $validator = $this->createMock(ValidatorInterface::class);
        $validator
            ->expects($this->once())
            ->method('validate')
            ->willReturn(
                new ConstraintViolationList([])
            )
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
            $userTokenService,
            $validator,
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
}
