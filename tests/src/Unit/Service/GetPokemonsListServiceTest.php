<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\ResponseObject\Election\ElectionList;
use App\Service\Back\GetPokemonsService;
use App\Service\GetPokemonsListService;
use App\Tests\Common\Traits\ResponseObjectTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(GetPokemonsListService::class)]
class GetPokemonsListServiceTest extends TestCase
{
    use ResponseObjectTrait;

    public function testGet(): void
    {
        $getPokemonsService = $this->createMock(GetPokemonsService::class);
        $getPokemonsService
            ->expects($this->once())
            ->method('get')
            ->with(
                'douze',
                '',
                12,
            )
            ->willReturn(
                new ElectionList(
                    'pick',
                    [
                        $this->getStubPokemon(),
                        $this->getStubPokemon(),
                    ]
                )
            )
        ;

        $service = new GetPokemonsListService($getPokemonsService, 12);
        $list = $service->get('douze', '', []);

        $this->assertSame('pick', $list->getType());
        $this->assertCount(2, $list->getItems());
    }

    public function testGetWithFilters(): void
    {
        $getPokemonsService = $this->createMock(GetPokemonsService::class);
        $getPokemonsService
            ->expects($this->once())
            ->method('get')
            ->with(
                'douze',
                '',
                12,
                ['at' => ['poison', 'fire'], 'cf' => ['legendary']],
            )
            ->willReturn(
                new ElectionList(
                    'pick',
                    [
                        $this->getStubPokemon(),
                        $this->getStubPokemon(),
                    ]
                )
            )
        ;

        $service = new GetPokemonsListService($getPokemonsService, 12);
        $list = $service->get('douze', '', ['at' => ['poison', 'fire'], 'cf' => ['legendary']]);

        $this->assertSame('pick', $list->getType());
        $this->assertCount(2, $list->getItems());
    }
}
