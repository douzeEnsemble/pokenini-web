<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Service\Back\GetPokedexService;
use App\Service\GetTrainerPokedexService;
use App\Tests\Common\Traits\ResponseObjectTrait;
use PHPUnit\Framework\Attributes\CoversClass;
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

    public function testGetPokedexData(): void
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

    public function testGetPokedexDataWithFilters(): void
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

    public function testGetPokedexDataWithTrainerId(): void
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

    public function testGetPokedexDataWithNullTrainerId(): void
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

    public function testGetPokedexDataWithEmptyTrainerId(): void
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

    public function testGetPokedexDataHttpException(): void
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

    public function testGetPokedexDataTransportException(): void
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
