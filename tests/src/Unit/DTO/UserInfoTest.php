<?php

declare(strict_types=1);

namespace App\Tests\Unit\DTO;

use App\DTO\UserInfo;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(UserInfo::class)]
final class UserInfoTest extends TestCase
{
    #[Test]
    public function construtorAndGetters(): void
    {
        $userInfo = new UserInfo(
            '20230321T0834470000',
            'testprovider',
            'collector',
            [
                'ROLE_TRAINER',
                'ROLE_COLLECTOR',
            ],
            'session-jwt-from-back',
        );

        $this->assertSame(
            '20230321T0834470000',
            $userInfo->getId(),
        );
        $this->assertSame(
            'testprovider',
            $userInfo->getProvider(),
        );
        $this->assertSame(
            'collector',
            $userInfo->getProfile(),
        );
        $this->assertSame(
            [
                'ROLE_TRAINER',
                'ROLE_COLLECTOR',
            ],
            $userInfo->getRoles(),
        );
        $this->assertSame(
            'session-jwt-from-back',
            $userInfo->getSessionToken(),
        );
    }
}
