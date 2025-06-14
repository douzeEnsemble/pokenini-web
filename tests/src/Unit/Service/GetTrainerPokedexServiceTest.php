<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Security\UserTokenService;
use App\Service\Api\GetPokedexService;
use App\Service\GetTrainerPokedexService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\HttpClient\Exception\HttpExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

/**
 * @internal
 */
#[CoversClass(GetTrainerPokedexService::class)]
class GetTrainerPokedexServiceTest extends TestCase
{
    public function testGetPokedexData(): void
    {
        $userTokenService = $this->createMock(UserTokenService::class);
        $userTokenService
            ->expects($this->once())
            ->method('getLoggedUserToken')
            ->willReturn('8800088')
        ;

        $getPokedexService = $this->createMock(GetPokedexService::class);
        $getPokedexService
            ->expects($this->once())
            ->method('get')
            ->with(
                'douze',
                '8800088',
                [],
            )
            ->willReturn([
                'dex' => [
                    'slug' => 'douze-douze',
                ],
                'pokemons' => [],
            ])
        ;

        $service = new GetTrainerPokedexService($userTokenService, $getPokedexService);
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
        $userTokenService = $this->createMock(UserTokenService::class);
        $userTokenService
            ->expects($this->once())
            ->method('getLoggedUserToken')
            ->willReturn('8800088')
        ;

        $getPokedexService = $this->createMock(GetPokedexService::class);
        $getPokedexService
            ->expects($this->once())
            ->method('get')
            ->with(
                'douze',
                '8800088',
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

        $service = new GetTrainerPokedexService($userTokenService, $getPokedexService);
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

    public function testGetPokedexDataByTrainerId(): void
    {
        $userTokenService = $this->createMock(UserTokenService::class);
        $userTokenService
            ->expects($this->never())
            ->method('getLoggedUserToken')
        ;

        $getPokedexService = $this->createMock(GetPokedexService::class);
        $getPokedexService
            ->expects($this->once())
            ->method('get')
            ->with(
                'douze',
                '8800088',
                [],
            )
            ->willReturn([
                'dex' => [
                    'slug' => 'douze-douze',
                ],
                'pokemons' => [],
            ])
        ;

        $service = new GetTrainerPokedexService($userTokenService, $getPokedexService);
        $pokedexData = $service->getPokedexDataByTrainerId('douze', [], '8800088');

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
        $userTokenService = $this->createMock(UserTokenService::class);
        $userTokenService
            ->expects($this->once())
            ->method('getLoggedUserToken')
            ->willReturn('8800088')
        ;

        $exception = $this->createMock(HttpExceptionInterface::class);

        $getPokedexService = $this->createMock(GetPokedexService::class);
        $getPokedexService
            ->expects($this->once())
            ->method('get')
            ->with(
                'douze',
                '8800088',
                [],
            )
            ->willThrowException($exception)
        ;

        $service = new GetTrainerPokedexService($userTokenService, $getPokedexService);
        $dexData = $service->getPokedexData('douze', []);

        $this->assertNull($dexData);
    }

    public function testGetPokedexDataTransportException(): void
    {
        $userTokenService = $this->createMock(UserTokenService::class);
        $userTokenService
            ->expects($this->once())
            ->method('getLoggedUserToken')
            ->willReturn('8800088')
        ;

        $exception = $this->createMock(TransportExceptionInterface::class);

        $getPokedexService = $this->createMock(GetPokedexService::class);
        $getPokedexService
            ->expects($this->once())
            ->method('get')
            ->with(
                'douze',
                '8800088',
                [],
            )
            ->willThrowException($exception)
        ;

        $service = new GetTrainerPokedexService($userTokenService, $getPokedexService);
        $dexData = $service->getPokedexData('douze', []);

        $this->assertNull($dexData);
    }
}
