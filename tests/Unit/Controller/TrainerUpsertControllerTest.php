<?php

declare(strict_types=1);

namespace App\Tests\Unit\Controller;

use App\Controller\TrainerUpsertController;
use App\Security\UserTokenService;
use App\Service\Api\ModifyDexService;
use App\Service\CacheInvalidator\AlbumCacheInvalidatorService;
use App\Service\CacheInvalidator\DexCacheInvalidatorService;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Symfony\Component\HttpClient\Exception\TransportException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class TrainerUpsertControllerTest extends TestCase
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

        $modifyDexService = $this->createMock(ModifyDexService::class);
        $modifyDexService
            ->expects($this->once())
            ->method('modify')
            ->with(
                'douze',
                '{}',
                '1234567890'
            )
        ;

        $albumCacheInvalidatorService = $this->createMock(AlbumCacheInvalidatorService::class);
        $albumCacheInvalidatorService
            ->expects($this->once())
            ->method('invalidate')
            ->with(
                'douze',
                '1234567890'
            )
        ;

        $dexCacheInvalidatorService = $this->createMock(DexCacheInvalidatorService::class);
        $dexCacheInvalidatorService
            ->expects($this->once())
            ->method('invalidateByTrainerId')
            ->with(
                '1234567890'
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

        $controller = new TrainerUpsertController(
            $userTokenService,
            $validator,
            $modifyDexService,
            $albumCacheInvalidatorService,
            $dexCacheInvalidatorService,
        );
        $controller->setContainer($container);

        $request = $this->createMock(Request::class);
        $request
            ->expects($this->once())
            ->method('getContent')
            ->willReturn('{}')
        ;

        $response = $controller->upsert('douze', $request);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEmpty($response->getContent());
    }

    public function testUpsertBadContent(): void
    {
        $userTokenService = $this->createMock(UserTokenService::class);

        $validator = $this->createMock(ValidatorInterface::class);

        $modifyDexService = $this->createMock(ModifyDexService::class);

        $albumCacheInvalidatorService = $this->createMock(AlbumCacheInvalidatorService::class);

        $dexCacheInvalidatorService = $this->createMock(DexCacheInvalidatorService::class);

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

        $controller = new TrainerUpsertController(
            $userTokenService,
            $validator,
            $modifyDexService,
            $albumCacheInvalidatorService,
            $dexCacheInvalidatorService,
        );
        $controller->setContainer($container);

        $request = $this->createMock(Request::class);
        $request
            ->expects($this->once())
            ->method('getContent')
            ->willReturn([])
        ;

        $response = $controller->upsert('douze', $request);

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

        $modifyDexService = $this->createMock(ModifyDexService::class);

        $albumCacheInvalidatorService = $this->createMock(AlbumCacheInvalidatorService::class);

        $dexCacheInvalidatorService = $this->createMock(DexCacheInvalidatorService::class);

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

        $controller = new TrainerUpsertController(
            $userTokenService,
            $validator,
            $modifyDexService,
            $albumCacheInvalidatorService,
            $dexCacheInvalidatorService,
        );
        $controller->setContainer($container);

        $request = $this->createMock(Request::class);
        $request
            ->expects($this->once())
            ->method('getContent')
            ->willReturn('')
        ;

        $response = $controller->upsert('douze', $request);

        $this->assertEquals(400, $response->getStatusCode());
        $this->assertEquals(
            '{"error":"Content must be a non-empty string"}',
            $response->getContent()
        );
    }

    public function testUpsertInvalidContent(): void
    {
        $userTokenService = $this->createMock(UserTokenService::class);

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

        $modifyDexService = $this->createMock(ModifyDexService::class);

        $albumCacheInvalidatorService = $this->createMock(AlbumCacheInvalidatorService::class);

        $dexCacheInvalidatorService = $this->createMock(DexCacheInvalidatorService::class);

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

        $controller = new TrainerUpsertController(
            $userTokenService,
            $validator,
            $modifyDexService,
            $albumCacheInvalidatorService,
            $dexCacheInvalidatorService,
        );
        $controller->setContainer($container);

        $request = $this->createMock(Request::class);
        $request
            ->expects($this->once())
            ->method('getContent')
            ->willReturn('{not": "a valid, json')
        ;

        $response = $controller->upsert('douze', $request);

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

        $modifyDexService = $this->createMock(ModifyDexService::class);
        $modifyDexService
            ->expects($this->once())
            ->method('modify')
            ->willThrowException(
                new TransportException('Whoops!')
            )
            ->with(
                'douze',
                '{}',
                '1234567890'
            )
        ;

        $albumCacheInvalidatorService = $this->createMock(AlbumCacheInvalidatorService::class);

        $dexCacheInvalidatorService = $this->createMock(DexCacheInvalidatorService::class);

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

        $controller = new TrainerUpsertController(
            $userTokenService,
            $validator,
            $modifyDexService,
            $albumCacheInvalidatorService,
            $dexCacheInvalidatorService,
        );
        $controller->setContainer($container);

        $request = $this->createMock(Request::class);
        $request
            ->expects($this->once())
            ->method('getContent')
            ->willReturn('{}')
        ;

        $response = $controller->upsert('douze', $request);

        $this->assertEquals(500, $response->getStatusCode());
        $this->assertEquals('{"error":"Whoops!"}', $response->getContent());
    }
}
