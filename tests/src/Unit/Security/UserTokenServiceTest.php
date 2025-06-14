<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security;

use App\Exception\NoLoggedUserException;
use App\Security\User;
use App\Security\UserTokenService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * @internal
 */
#[CoversClass(UserTokenService::class)]
class UserTokenServiceTest extends TestCase
{
    public function testGetLoggedUserToken(): void
    {
        $security = $this->createMock(Security::class);
        $security
            ->expects($this->once())
            ->method('getUser')
            ->willReturn(new User('12', 'TestProvider'))
        ;

        $service = new UserTokenService($security);
        $this->assertEquals(
            '7b52009b64fd0a2a49e6d8a939753077792b0554',
            $service->getLoggedUserToken()
        );
    }

    public function testFailGetLoggedUserToken(): void
    {
        $security = $this->createMock(Security::class);

        $service = new UserTokenService($security);

        $this->expectException(NoLoggedUserException::class);
        $service->getLoggedUserToken();
    }
}
