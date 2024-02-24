<?php

declare(strict_types=1);

namespace App\Tests\Unit\Controller;

use App\Controller\AlbumUpsertController;
use App\Security\UserTokenService;
use App\Service\Api\ModifyAlbumService;
use App\Service\CacheInvalidator\AlbumsCacheInvalidatorService;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Symfony\Component\HttpClient\Exception\TransportException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class AlbumUpsertControllerTest extends TestCase
{
    public function testUpsert(): void
    {
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

        $modifyAlbumService = $this->createMock(ModifyAlbumService::class);
        $modifyAlbumService
            ->expects($this->once())
            ->method('modify')
            ->with(
                'PATCH',
                'douze',
                'machi',
                '{}',
                '1234567890'
            )
        ;

        $albumsCacheInvalidatorService = $this->createMock(AlbumsCacheInvalidatorService::class);
        $albumsCacheInvalidatorService
            ->expects($this->once())
            ->method('invalidate')
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

        $controller = new AlbumUpsertController(
            $userTokenService,
            $validator,
            $modifyAlbumService,
            $albumsCacheInvalidatorService,
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
        $userTokenService = $this->createMock(UserTokenService::class);

        $validator = $this->createMock(ValidatorInterface::class);

        $modifyAlbumService = $this->createMock(ModifyAlbumService::class);

        $albumsCacheInvalidatorService = $this->createMock(AlbumsCacheInvalidatorService::class);

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

        $controller = new AlbumUpsertController(
            $userTokenService,
            $validator,
            $modifyAlbumService,
            $albumsCacheInvalidatorService,
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
        $userTokenService = $this->createMock(UserTokenService::class);

        $validator = $this->createMock(ValidatorInterface::class);

        $modifyAlbumService = $this->createMock(ModifyAlbumService::class);

        $albumsCacheInvalidatorService = $this->createMock(AlbumsCacheInvalidatorService::class);

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

        $controller = new AlbumUpsertController(
            $userTokenService,
            $validator,
            $modifyAlbumService,
            $albumsCacheInvalidatorService,
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

        $modifyAlbumService = $this->createMock(ModifyAlbumService::class);

        $albumsCacheInvalidatorService = $this->createMock(AlbumsCacheInvalidatorService::class);

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

        $controller = new AlbumUpsertController(
            $userTokenService,
            $validator,
            $modifyAlbumService,
            $albumsCacheInvalidatorService,
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

        $modifyAlbumService = $this->createMock(ModifyAlbumService::class);
        $modifyAlbumService
            ->expects($this->once())
            ->method('modify')
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

        $albumsCacheInvalidatorService = $this->createMock(AlbumsCacheInvalidatorService::class);
        $albumsCacheInvalidatorService
            ->expects($this->never())
            ->method('invalidate')
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

        $controller = new AlbumUpsertController(
            $userTokenService,
            $validator,
            $modifyAlbumService,
            $albumsCacheInvalidatorService,
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

        $this->assertEquals(500, $response->getStatusCode());
        $this->assertEquals('{"error":"Whoops!"}', $response->getContent());
    }
}
