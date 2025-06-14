<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\DTO\ElectionPokemonsList;
use App\Security\UserTokenService;
use App\Service\Api\GetPokemonsService;
use App\Service\GetPokemonsListService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(GetPokemonsListService::class)]
class GetPokemonsListServiceTest extends TestCase
{
    public function testGet(): void
    {
        $userTokenService = $this->createMock(UserTokenService::class);
        $userTokenService
            ->expects($this->once())
            ->method('getLoggedUserToken')
            ->willReturn('8800088')
        ;

        $getPokemonsService = $this->createMock(GetPokemonsService::class);
        $getPokemonsService
            ->expects($this->once())
            ->method('get')
            ->with(
                '8800088',
                'douze',
                '',
                12,
            )
            ->willReturn(
                new ElectionPokemonsList(
                    [
                        'type' => 'pick',
                        'items' => [
                            [
                                'poke' => '1',
                                'numb' => 1,
                                'exist' => null,
                            ],
                            [
                                'poke' => '2',
                                'numb' => 2,
                                'exist' => null,
                            ],
                        ],
                    ]
                )
            )
        ;

        $service = new GetPokemonsListService($userTokenService, $getPokemonsService, 12);
        $list = $service->get('douze', '', []);

        $this->assertSame('pick', $list->type);
        $this->assertSame(
            [
                [
                    'poke' => '1',
                    'numb' => 1,
                    'exist' => null,
                ],
                [
                    'poke' => '2',
                    'numb' => 2,
                    'exist' => null,
                ],
            ],
            $list->items
        );
    }

    public function testGetWithFilters(): void
    {
        $userTokenService = $this->createMock(UserTokenService::class);
        $userTokenService
            ->expects($this->once())
            ->method('getLoggedUserToken')
            ->willReturn('8800088')
        ;

        $getPokemonsService = $this->createMock(GetPokemonsService::class);
        $getPokemonsService
            ->expects($this->once())
            ->method('get')
            ->with(
                '8800088',
                'douze',
                '',
                12,
                ['at' => ['poison', 'fire'], 'cf' => ['legendary']],
            )
            ->willReturn(
                new ElectionPokemonsList(
                    [
                        'type' => 'pick',
                        'items' => [
                            [
                                'poke' => '1',
                                'numb' => 1,
                                'exist' => null,
                            ],
                            [
                                'poke' => '2',
                                'numb' => 2,
                                'exist' => null,
                            ],
                        ],
                    ]
                )
            )
        ;

        $service = new GetPokemonsListService($userTokenService, $getPokemonsService, 12);
        $list = $service->get('douze', '', ['at' => ['poison', 'fire'], 'cf' => ['legendary']]);

        $this->assertSame('pick', $list->type);
        $this->assertSame(
            [
                [
                    'poke' => '1',
                    'numb' => 1,
                    'exist' => null,
                ],
                [
                    'poke' => '2',
                    'numb' => 2,
                    'exist' => null,
                ],
            ],
            $list->items
        );
    }
}
