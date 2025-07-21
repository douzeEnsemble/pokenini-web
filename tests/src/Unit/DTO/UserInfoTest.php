<?php

declare(strict_types=1);

namespace App\Tests\Unit\DTO;

use App\DTO\UserInfo;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(UserInfo::class)]
class UserInfoTest extends TestCase
{
    public function testCreateFromArray(): void
    {
        $userInfo = UserInfo::createFromArray([
            'identifier' => '20230321T0834470000',
            'roles' => [
                'ROLE_TRAINER',
                'ROLE_COLLECTOR',
            ],
        ]);

        $this->assertEquals(
            '20230321T0834470000',
            $userInfo->identifier,
        );
        $this->assertEquals(
            [
                'ROLE_TRAINER',
                'ROLE_COLLECTOR',
            ],
            $userInfo->roles,
        );
    }
}
