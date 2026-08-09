<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Service\Back\GetPokedexService;
use App\Service\GetTrainerPokedexService;
use App\Tests\Common\Traits\ResponseObjectTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\HttpClient\Exception\HttpExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

/**
 * @internal
 */
#[CoversClass(GetPokedexService::class)]
#[CoversClass(GetTrainerPokedexService::class)]
final class GetTrainerPokedexServiceTest extends TestCase
{
    use ResponseObjectTrait;

    #[Test]
    public function getPokedexData(): void
    {
        $getPokedexService = $this->createMock(GetPokedexService::class);
        $getPokedexService
            ->expects($this->once())
            ->method('get')
            ->with(
                'douze',
                [],
            )
            ->willReturn($this->getStubAlbum())
        ;
        $getPokedexService
            ->expects($this->never())
            ->method('getWithTrainerId')
        ;

        $service = new GetTrainerPokedexService($getPokedexService);
        $pokedexData = $service->getPokedexData('douze', []);

        $this->assertNotNull($pokedexData);
        $this->assertSame('Stub', $pokedexData->getPokedex()->getDex()?->getName());
        $this->assertCount(1, $pokedexData->getPokedex()->getPokemons());
        $this->assertSame(2, $pokedexData->getPokedex()->getReport()->getTotalCaught());
        $this->assertSame(0, $pokedexData->getPokedex()->getFilteredReport()->getTotalCaught());
    }

    #[Test]
    public function getPokedexDataWithFilters(): void
    {
        $getPokedexService = $this->createMock(GetPokedexService::class);
        $getPokedexService
            ->expects($this->once())
            ->method('get')
            ->with(
                'douze',
                [
                    'to' => 'toto',
                    'ti' => 'titi',
                ],
            )
            ->willReturn($this->getStubAlbum())
        ;
        $getPokedexService
            ->expects($this->never())
            ->method('getWithTrainerId')
        ;

        $service = new GetTrainerPokedexService($getPokedexService);
        $pokedexData = $service->getPokedexData(
            'douze',
            [
                'to' => 'toto',
                'ti' => 'titi',
            ]
        );

        $this->assertNotNull($pokedexData);
        $this->assertSame('Stub', $pokedexData->getPokedex()->getDex()?->getName());
        $this->assertCount(1, $pokedexData->getPokedex()->getPokemons());
        $this->assertSame(2, $pokedexData->getPokedex()->getReport()->getTotalCaught());
        $this->assertSame(0, $pokedexData->getPokedex()->getFilteredReport()->getTotalCaught());
    }

    #[Test]
    public function getPokedexDataWithTrainerId(): void
    {
        $getPokedexService = $this->createMock(GetPokedexService::class);
        $getPokedexService
            ->expects($this->never())
            ->method('get')
        ;
        $getPokedexService
            ->expects($this->once())
            ->method('getWithTrainerId')
            ->with(
                '8800088',
                'douze',
                [],
            )
            ->willReturn($this->getStubAlbum())
        ;

        $service = new GetTrainerPokedexService($getPokedexService);
        $pokedexData = $service->getPokedexData('douze', [], '8800088');

        $this->assertNotNull($pokedexData);
        $this->assertSame('Stub', $pokedexData->getPokedex()->getDex()?->getName());
        $this->assertCount(1, $pokedexData->getPokedex()->getPokemons());
        $this->assertSame(2, $pokedexData->getPokedex()->getReport()->getTotalCaught());
        $this->assertSame(0, $pokedexData->getPokedex()->getFilteredReport()->getTotalCaught());
    }

    #[Test]
    public function getPokedexDataWithNullTrainerId(): void
    {
        $getPokedexService = $this->createMock(GetPokedexService::class);
        $getPokedexService
            ->expects($this->once())
            ->method('get')
            ->with(
                'douze',
                [],
            )
            ->willReturn($this->getStubAlbum())
        ;
        $getPokedexService
            ->expects($this->never())
            ->method('getWithTrainerId')
        ;

        $service = new GetTrainerPokedexService($getPokedexService);
        $pokedexData = $service->getPokedexData('douze', [], null);

        $this->assertNotNull($pokedexData);
        $this->assertSame('Stub', $pokedexData->getPokedex()->getDex()?->getName());
        $this->assertCount(1, $pokedexData->getPokedex()->getPokemons());
        $this->assertSame(2, $pokedexData->getPokedex()->getReport()->getTotalCaught());
        $this->assertSame(0, $pokedexData->getPokedex()->getFilteredReport()->getTotalCaught());
    }

    #[Test]
    public function getPokedexDataWithEmptyTrainerId(): void
    {
        $getPokedexService = $this->createMock(GetPokedexService::class);
        $getPokedexService
            ->expects($this->once())
            ->method('get')
            ->with(
                'douze',
                [],
            )
            ->willReturn($this->getStubAlbum())
        ;
        $getPokedexService
            ->expects($this->never())
            ->method('getWithTrainerId')
        ;

        $service = new GetTrainerPokedexService($getPokedexService);
        $pokedexData = $service->getPokedexData('douze', [], '');

        $this->assertNotNull($pokedexData);
        $this->assertSame('Stub', $pokedexData->getPokedex()->getDex()?->getName());
        $this->assertCount(1, $pokedexData->getPokedex()->getPokemons());
        $this->assertSame(2, $pokedexData->getPokedex()->getReport()->getTotalCaught());
        $this->assertSame(0, $pokedexData->getPokedex()->getFilteredReport()->getTotalCaught());
    }

    #[Test]
    public function getPokedexDataHttpException(): void
    {
        $exception = $this->createStub(HttpExceptionInterface::class);

        $getPokedexService = $this->createMock(GetPokedexService::class);
        $getPokedexService
            ->expects($this->once())
            ->method('get')
            ->with(
                'douze',
                [],
            )
            ->willThrowException($exception)
        ;
        $getPokedexService
            ->expects($this->never())
            ->method('getWithTrainerId')
        ;

        $service = new GetTrainerPokedexService($getPokedexService);
        $dexData = $service->getPokedexData('douze', []);

        $this->assertNull($dexData);
    }

    #[Test]
    public function getPokedexDataTransportException(): void
    {
        $exception = $this->createStub(TransportExceptionInterface::class);

        $getPokedexService = $this->createMock(GetPokedexService::class);
        $getPokedexService
            ->expects($this->once())
            ->method('get')
            ->with(
                'douze',
                [],
            )
            ->willThrowException($exception)
        ;
        $getPokedexService
            ->expects($this->never())
            ->method('getWithTrainerId')
        ;

        $service = new GetTrainerPokedexService($getPokedexService);
        $dexData = $service->getPokedexData('douze', []);

        $this->assertNull($dexData);
    }
}
