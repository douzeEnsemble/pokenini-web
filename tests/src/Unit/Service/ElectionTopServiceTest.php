<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Service\Back\GetElectionTopService;
use App\Service\ElectionTopService;
use App\Tests\Common\Traits\ResponseObjectTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(ElectionTopService::class)]
final class ElectionTopServiceTest extends TestCase
{
    use ResponseObjectTrait;

    public function testGetTop(): void
    {
        $apiService = $this->createMock(GetElectionTopService::class);
        $apiService
            ->expects($this->once())
            ->method('getTop')
            ->with(
                'demo',
                'whatever',
                12,
            )
            ->willReturn($this->getStubElectionTop())
        ;

        $service = new ElectionTopService($apiService, 12);

        $object = $service->getTop('demo', 'whatever');

        $this->assertCount(3, $object->getItems());
    }
}
