<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Exception\ModifyFailedException;
use App\Security\UserTokenService;
use App\Service\Api\ModifyDexService;
use App\Service\CacheInvalidator\AlbumCacheInvalidatorService;
use App\Service\CacheInvalidator\DexCacheInvalidatorService;
use App\Service\ModifyTrainerDexService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\HttpClient\Exception\HttpExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

/**
 * @internal
 */
#[CoversClass(ModifyTrainerDexService::class)]
class ModifyTrainerDexServiceTest extends TestCase
{
    public function testModifyDex(): void
    {
        $userTokenService = $this->createMock(UserTokenService::class);
        $userTokenService
            ->expects($this->once())
            ->method('getLoggedUserToken')
            ->willReturn('8800088')
        ;

        $modifyDexService = $this->createMock(ModifyDexService::class);
        $modifyDexService
            ->expects($this->once())
            ->method('modify')
            ->with(
                'douze',
                '{"ceci": "est-du-contenu"}',
                '8800088',
            )
        ;

        $albumCacheInvalidatorService = $this->createMock(AlbumCacheInvalidatorService::class);
        $albumCacheInvalidatorService
            ->expects($this->once())
            ->method('invalidate')
            ->with(
                'douze',
                '8800088',
            )
        ;

        $dexCacheInvalidatorService = $this->createMock(DexCacheInvalidatorService::class);
        $dexCacheInvalidatorService
            ->expects($this->once())
            ->method('invalidateByTrainerId')
            ->with(
                '8800088',
            )
        ;

        $service = new ModifyTrainerDexService(
            $userTokenService,
            $modifyDexService,
            $albumCacheInvalidatorService,
            $dexCacheInvalidatorService
        );
        $service->modifyDex('douze', '{"ceci": "est-du-contenu"}');
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

        $modifyDexService = $this->createMock(ModifyDexService::class);
        $modifyDexService
            ->expects($this->once())
            ->method('modify')
            ->with(
                'douze',
                '{"ceci": "est-du-contenu"}',
                '8800088',
            )
            ->willThrowException($exception)
        ;

        $albumCacheInvalidatorService = $this->createMock(AlbumCacheInvalidatorService::class);
        $albumCacheInvalidatorService
            ->expects($this->never())
            ->method('invalidate')
        ;

        $dexCacheInvalidatorService = $this->createMock(DexCacheInvalidatorService::class);
        $dexCacheInvalidatorService
            ->expects($this->never())
            ->method('invalidateByTrainerId')
        ;

        $service = new ModifyTrainerDexService(
            $userTokenService,
            $modifyDexService,
            $albumCacheInvalidatorService,
            $dexCacheInvalidatorService
        );

        $this->expectException(ModifyFailedException::class);

        $service->modifyDex('douze', '{"ceci": "est-du-contenu"}');
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

        $modifyDexService = $this->createMock(ModifyDexService::class);
        $modifyDexService
            ->expects($this->once())
            ->method('modify')
            ->with(
                'douze',
                '{"ceci": "est-du-contenu"}',
                '8800088',
            )
            ->willThrowException($exception)
        ;

        $albumCacheInvalidatorService = $this->createMock(AlbumCacheInvalidatorService::class);
        $albumCacheInvalidatorService
            ->expects($this->never())
            ->method('invalidate')
        ;

        $dexCacheInvalidatorService = $this->createMock(DexCacheInvalidatorService::class);
        $dexCacheInvalidatorService
            ->expects($this->never())
            ->method('invalidateByTrainerId')
        ;

        $service = new ModifyTrainerDexService(
            $userTokenService,
            $modifyDexService,
            $albumCacheInvalidatorService,
            $dexCacheInvalidatorService
        );

        $this->expectException(ModifyFailedException::class);

        $service->modifyDex('douze', '{"ceci": "est-du-contenu"}');
    }
}
