<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security;

use App\Exception\NoLoggedUserException;
use App\Security\User;
use App\Security\UserTokenService;
use League\OAuth2\Client\Token\AccessToken;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * @internal
 */
#[CoversClass(UserTokenService::class)]
class UserTokenServiceTest extends TestCase
{
    public function testGetLoggedUserId(): void
    {
        $security = $this->createMock(Security::class);
        $security
            ->expects($this->once())
            ->method('getUser')
            ->willReturn(
                new User(
                    '12',
                    'TestProvider',
                    new AccessToken(['access_token' => 'd546354']),
                ),
            )
        ;

        $service = new UserTokenService($security);
        $this->assertEquals(
            '7b52009b64fd0a2a49e6d8a939753077792b0554',
            $service->getLoggedUserId()
        );
    }

    public function testFailGetLoggedUserId(): void
    {
        $security = $this->createMock(Security::class);

        $service = new UserTokenService($security);

        $this->expectException(NoLoggedUserException::class);
        $service->getLoggedUserId();
    }

    public function testCompare(): void
    {
        $security = $this->createMock(Security::class);
        $security
            ->expects($this->once())
            ->method('getUser')
            ->willReturn(
                new User(
                    '12',
                    'TestProvider',
                    new AccessToken(['access_token' => 'd546354']),
                ),
            )
        ;

        $service = new UserTokenService($security);
        $this->assertTrue($service->compare('7b52009b64fd0a2a49e6d8a939753077792b0554'));
    }

    public function testCompareWithNoLoggedUser(): void
    {
        $security = $this->createMock(Security::class);
        $security
            ->expects($this->once())
            ->method('getUser')
            ->willReturn(null)
        ;

        $service = new UserTokenService($security);
        $this->assertFalse($service->compare('7b52009b64fd0a2a49e6d8a939753077792b0554'));
    }

    public function testGetLoggedUserToken(): void
    {
        $security = $this->createMock(Security::class);
        $security
            ->expects($this->once())
            ->method('getUser')
            ->willReturn(
                new User(
                    '12',
                    'TestProvider',
                    new AccessToken(['access_token' => 'd546354']),
                ),
            )
        ;

        $service = new UserTokenService($security);
        $this->assertEquals(
            'd546354',
            $service->getLoggedUserToken()
        );
    }

    public function testGetLoggedUserTokenWithoutUser(): void
    {
        $security = $this->createMock(Security::class);
        $security
            ->expects($this->once())
            ->method('getUser')
            ->willReturn(null)
        ;

        $service = new UserTokenService($security);

        $this->expectException(NoLoggedUserException::class);
        $this->expectExceptionMessage('No user logged');

        $service->getLoggedUserToken();
    }
}
