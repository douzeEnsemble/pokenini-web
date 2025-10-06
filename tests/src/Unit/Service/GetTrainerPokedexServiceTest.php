<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Service\Back\GetPokedexService;
use App\Service\GetTrainerPokedexService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\HttpClient\Exception\HttpExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

/**
 * @internal
 */
#[CoversClass(GetPokedexService::class)]
#[CoversClass(GetTrainerPokedexService::class)]
class GetTrainerPokedexServiceTest extends TestCase
{
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
            ->willReturn([
                'dex' => [
                    'slug' => 'douze-douze',
                ],
                'pokemons' => [],
            ])
        ;
        $getPokedexService
            ->expects($this->never())
            ->method('getWithTrainerId')
        ;

        $service = new GetTrainerPokedexService($getPokedexService);
        $pokedexData = $service->getPokedexData('douze', []);

        $this->assertSame(
            [
                'dex' => [
                    'slug' => 'douze-douze',
                ],
                'pokemons' => [],
            ],
            $pokedexData,
        );
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
            ->willReturn([
                'dex' => [
                    'slug' => 'douze-douze',
                ],
                'pokemons' => [],
            ])
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

        $this->assertSame(
            [
                'dex' => [
                    'slug' => 'douze-douze',
                ],
                'pokemons' => [],
            ],
            $pokedexData,
        );
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
            ->willReturn([
                'dex' => [
                    'slug' => 'douze-douze',
                ],
                'pokemons' => [],
            ])
        ;

        $service = new GetTrainerPokedexService($getPokedexService);
        $pokedexData = $service->getPokedexData('douze', [], '8800088');

        $this->assertSame(
            [
                'dex' => [
                    'slug' => 'douze-douze',
                ],
                'pokemons' => [],
            ],
            $pokedexData,
        );
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
            ->willReturn([
                'dex' => [
                    'slug' => 'douze-douze',
                ],
                'pokemons' => [],
            ])
        ;
        $getPokedexService
            ->expects($this->never())
            ->method('getWithTrainerId')
        ;

        $service = new GetTrainerPokedexService($getPokedexService);
        $pokedexData = $service->getPokedexData('douze', [], null);

        $this->assertSame(
            [
                'dex' => [
                    'slug' => 'douze-douze',
                ],
                'pokemons' => [],
            ],
            $pokedexData,
        );
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
            ->willReturn([
                'dex' => [
                    'slug' => 'douze-douze',
                ],
                'pokemons' => [],
            ])
        ;
        $getPokedexService
            ->expects($this->never())
            ->method('getWithTrainerId')
        ;

        $service = new GetTrainerPokedexService($getPokedexService);
        $pokedexData = $service->getPokedexData('douze', [], '');

        $this->assertSame(
            [
                'dex' => [
                    'slug' => 'douze-douze',
                ],
                'pokemons' => [],
            ],
            $pokedexData,
        );
    }

    public function testGetPokedexDataHttpException(): void
    {
        $exception = $this->createMock(HttpExceptionInterface::class);

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
        $exception = $this->createMock(TransportExceptionInterface::class);

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
