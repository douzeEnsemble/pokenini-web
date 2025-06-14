<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Exception\ModifyFailedException;
use App\Security\UserTokenService;
use App\Service\Api\ModifyAlbumService;
use App\Service\CacheInvalidator\AlbumsCacheInvalidatorService;
use App\Service\ModifyTrainerAlbumService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\HttpClient\Exception\HttpExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

/**
 * @internal
 */
#[CoversClass(ModifyTrainerAlbumService::class)]
class ModifyTrainerAlbumServiceTest extends TestCase
{
    public function testModifyAlbum(): void
    {
        $userTokenService = $this->createMock(UserTokenService::class);
        $userTokenService
            ->expects($this->once())
            ->method('getLoggedUserToken')
            ->willReturn('8800088')
        ;

        $modifyAlbumService = $this->createMock(ModifyAlbumService::class);
        $modifyAlbumService
            ->expects($this->once())
            ->method('modify')
            ->with(
                'PUT',
                'douze',
                'treize',
                '{"ceci": "est-du-contenu"}',
                '8800088',
            )
        ;

        $albumsCacheInvalidatorService = $this->createMock(AlbumsCacheInvalidatorService::class);
        $albumsCacheInvalidatorService
            ->expects($this->once())
            ->method('invalidate')
        ;

        $request = Request::create(
            'test.local',
            'PUT',
        );
        $requestStack = new RequestStack();
        $requestStack->push($request);

        $service = new ModifyTrainerAlbumService(
            $userTokenService,
            $modifyAlbumService,
            $albumsCacheInvalidatorService,
            $requestStack,
        );
        $service->modifyAlbum('douze', 'treize', '{"ceci": "est-du-contenu"}');
    }

    public function testModifyDexWithHttpException(): void
    {
        $userTokenService = $this->createMock(UserTokenService::class);
        $userTokenService
            ->expects($this->once())
            ->method('getLoggedUserToken')
            ->willReturn('8800088')
        ;

        $exception = $this->createMock(HttpExceptionInterface::class);

        $modifyAlbumService = $this->createMock(ModifyAlbumService::class);
        $modifyAlbumService
            ->expects($this->once())
            ->method('modify')
            ->with(
                'PUT',
                'douze',
                'treize',
                '{"ceci": "est-du-contenu"}',
                '8800088',
            )
            ->willThrowException($exception)
        ;

        $albumsCacheInvalidatorService = $this->createMock(AlbumsCacheInvalidatorService::class);
        $albumsCacheInvalidatorService
            ->expects($this->never())
            ->method('invalidate')
        ;

        $request = Request::create(
            'test.local',
            'PUT',
        );
        $requestStack = new RequestStack();
        $requestStack->push($request);

        $service = new ModifyTrainerAlbumService(
            $userTokenService,
            $modifyAlbumService,
            $albumsCacheInvalidatorService,
            $requestStack,
        );

        $this->expectException(ModifyFailedException::class);

        $service->modifyAlbum('douze', 'treize', '{"ceci": "est-du-contenu"}');
    }

    public function testModifyDexWithNoRequest(): void
    {
        $userTokenService = $this->createMock(UserTokenService::class);
        $userTokenService
            ->expects($this->once())
            ->method('getLoggedUserToken')
            ->willReturn('8800088')
        ;

        $modifyAlbumService = $this->createMock(ModifyAlbumService::class);
        $modifyAlbumService
            ->expects($this->never())
            ->method('modify')
        ;

        $albumsCacheInvalidatorService = $this->createMock(AlbumsCacheInvalidatorService::class);
        $albumsCacheInvalidatorService
            ->expects($this->never())
            ->method('invalidate')
        ;

        $requestStack = new RequestStack();

        $service = new ModifyTrainerAlbumService(
            $userTokenService,
            $modifyAlbumService,
            $albumsCacheInvalidatorService,
            $requestStack,
        );

        $this->expectException(ModifyFailedException::class);

        $service->modifyAlbum('douze', 'treize', '{"ceci": "est-du-contenu"}');
    }

    public function testModifyDexWithTransportException(): void
    {
        $userTokenService = $this->createMock(UserTokenService::class);
        $userTokenService
            ->expects($this->once())
            ->method('getLoggedUserToken')
            ->willReturn('8800088')
        ;

        $exception = $this->createMock(TransportExceptionInterface::class);

        $modifyAlbumService = $this->createMock(ModifyAlbumService::class);
        $modifyAlbumService
            ->expects($this->once())
            ->method('modify')
            ->with(
                'PUT',
                'douze',
                'treize',
                '{"ceci": "est-du-contenu"}',
                '8800088',
            )
            ->willThrowException($exception)
        ;

        $albumsCacheInvalidatorService = $this->createMock(AlbumsCacheInvalidatorService::class);
        $albumsCacheInvalidatorService
            ->expects($this->never())
            ->method('invalidate')
        ;

        $request = Request::create(
            'test.local',
            'PUT',
        );
        $requestStack = new RequestStack();
        $requestStack->push($request);

        $service = new ModifyTrainerAlbumService(
            $userTokenService,
            $modifyAlbumService,
            $albumsCacheInvalidatorService,
            $requestStack,
        );

        $this->expectException(ModifyFailedException::class);

        $service->modifyAlbum('douze', 'treize', '{"ceci": "est-du-contenu"}');
    }
}
