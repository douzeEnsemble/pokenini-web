<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Security\UserTokenService;
use App\Service\Api\ElectionTopApiService;
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
        $userTokenService = $this->createMock(UserTokenService::class);
        $userTokenService
            ->expects($this->once())
            ->method('getLoggedUserToken')
            ->willReturn('8800088')
        ;

        $apiService = $this->createMock(ElectionTopApiService::class);
        $apiService
            ->expects($this->once())
            ->method('getTop')
            ->with(
                '8800088',
                'demo',
                'whatever',
                12,
            )
            ->willReturn(['some', 'data'])
        ;

        $service = new ElectionTopService($userTokenService, $apiService, 12);

        $this->assertSame(
            ['some', 'data'],
            $service->getTop('demo', 'whatever'),
        );
    }
}
