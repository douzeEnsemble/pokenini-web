<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Exception\ModifyFailedException;
use App\Service\Back\ModifyAlbumService;
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
final class ModifyTrainerAlbumServiceTest extends TestCase
{
    public function testModifyAlbum(): void
    {
        $modifyAlbumService = $this->createMock(ModifyAlbumService::class);
        $modifyAlbumService
            ->expects($this->once())
            ->method('modify')
            ->with(
                'PUT',
                'douze',
                'treize',
                '{"ceci": "est-du-contenu"}',
            )
        ;

        $request = Request::create(
            'test.local',
            'PUT',
        );
        $requestStack = new RequestStack();
        $requestStack->push($request);

        $service = new ModifyTrainerAlbumService(
            $modifyAlbumService,
            $requestStack,
        );
        $service->modifyAlbum('douze', 'treize', '{"ceci": "est-du-contenu"}');
    }

    public function testModifyDexWithHttpException(): void
    {
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
            )
            ->willThrowException($exception)
        ;

        $request = Request::create(
            'test.local',
            'PUT',
        );
        $requestStack = new RequestStack();
        $requestStack->push($request);

        $service = new ModifyTrainerAlbumService(
            $modifyAlbumService,
            $requestStack,
        );

        $this->expectException(ModifyFailedException::class);

        $service->modifyAlbum('douze', 'treize', '{"ceci": "est-du-contenu"}');
    }

    public function testModifyDexWithNoRequest(): void
    {
        $modifyAlbumService = $this->createMock(ModifyAlbumService::class);
        $modifyAlbumService
            ->expects($this->never())
            ->method('modify')
        ;

        $requestStack = new RequestStack();

        $service = new ModifyTrainerAlbumService(
            $modifyAlbumService,
            $requestStack,
        );

        $this->expectException(ModifyFailedException::class);

        $service->modifyAlbum('douze', 'treize', '{"ceci": "est-du-contenu"}');
    }

    public function testModifyDexWithTransportException(): void
    {
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
            )
            ->willThrowException($exception)
        ;

        $request = Request::create(
            'test.local',
            'PUT',
        );
        $requestStack = new RequestStack();
        $requestStack->push($request);

        $service = new ModifyTrainerAlbumService(
            $modifyAlbumService,
            $requestStack,
        );

        $this->expectException(ModifyFailedException::class);

        $service->modifyAlbum('douze', 'treize', '{"ceci": "est-du-contenu"}');
    }
}
