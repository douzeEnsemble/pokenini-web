<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Service\Back\GetElectionTopService;
use App\Service\ElectionTopService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(ElectionTopService::class)]
class ElectionTopServiceTest extends TestCase
{
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
            ->willReturn(['some', 'data'])
        ;

        $service = new ElectionTopService($apiService, 12);

        $this->assertSame(
            ['some', 'data'],
            $service->getTop('demo', 'whatever'),
        );
    }
}
