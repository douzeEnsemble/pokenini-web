<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Exception\ModifyFailedException;
use App\Service\Back\ModifyDexService;
use App\Service\ModifyTrainerDexService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\HttpClient\Exception\HttpExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

/**
 * @internal
 */
#[CoversClass(ModifyTrainerDexService::class)]
final class ModifyTrainerDexServiceTest extends TestCase
{
    public function testModifyDex(): void
    {
        $modifyDexService = $this->createMock(ModifyDexService::class);
        $modifyDexService
            ->expects($this->once())
            ->method('modify')
            ->with(
                'douze',
                '{"ceci": "est-du-contenu"}',
            )
        ;

        $service = new ModifyTrainerDexService(
            $modifyDexService,
        );
        $service->modifyDex('douze', '{"ceci": "est-du-contenu"}');
    }

    public function testModifyDexWithHttpException(): void
    {
        $exception = $this->createMock(HttpExceptionInterface::class);

        $modifyDexService = $this->createMock(ModifyDexService::class);
        $modifyDexService
            ->expects($this->once())
            ->method('modify')
            ->with(
                'douze',
                '{"ceci": "est-du-contenu"}',
            )
            ->willThrowException($exception)
        ;

        $service = new ModifyTrainerDexService(
            $modifyDexService,
        );

        $this->expectException(ModifyFailedException::class);

        $service->modifyDex('douze', '{"ceci": "est-du-contenu"}');
    }

    public function testModifyDexWithTransportException(): void
    {
        $exception = $this->createMock(TransportExceptionInterface::class);

        $modifyDexService = $this->createMock(ModifyDexService::class);
        $modifyDexService
            ->expects($this->once())
            ->method('modify')
            ->with(
                'douze',
                '{"ceci": "est-du-contenu"}',
            )
            ->willThrowException($exception)
        ;

        $service = new ModifyTrainerDexService(
            $modifyDexService,
        );

        $this->expectException(ModifyFailedException::class);

        $service->modifyDex('douze', '{"ceci": "est-du-contenu"}');
    }
}
