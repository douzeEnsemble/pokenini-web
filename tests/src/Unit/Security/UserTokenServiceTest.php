<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security;

use App\Exception\NoLoggedUserException;
use App\Security\User;
use App\Security\UserTokenService;
use League\OAuth2\Client\Token\AccessToken;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * @internal
 */
#[CoversClass(UserTokenService::class)]
final class UserTokenServiceTest extends TestCase
{
    #[Test]
    public function getLoggedUserId(): void
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
                    'test-session-token',
                ),
            )
        ;

        $service = new UserTokenService($security);
        $this->assertEquals(
            '7b52009b64fd0a2a49e6d8a939753077792b0554',
            $service->getLoggedUserId()
        );
    }

    #[Test]
    public function failGetLoggedUserId(): void
    {
        $security = $this->createMock(Security::class);
        $security
            ->expects($this->once())
            ->method('getUser')
            ->willReturn(null)
        ;

        $service = new UserTokenService($security);

        $this->expectException(NoLoggedUserException::class);
        $this->expectExceptionMessageIsOrContains('No user logged');
        $service->getLoggedUserId();
    }

    #[Test]
    public function compare(): void
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
                    'test-session-token',
                ),
            )
        ;

        $service = new UserTokenService($security);
        $this->assertTrue($service->compare('7b52009b64fd0a2a49e6d8a939753077792b0554'));
    }

    #[Test]
    public function compareWithNoLoggedUser(): void
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

    #[Test]
    public function getLoggedUser(): void
    {
        $user = new User(
            '12',
            'TestProvider',
            new AccessToken(['access_token' => 'd546354']),
            'test-session-token',
        );

        $security = $this->createMock(Security::class);
        $security
            ->expects($this->once())
            ->method('getUser')
            ->willReturn($user)
        ;

        $service = new UserTokenService($security);

        $this->assertEquals(
            $user,
            $service->getLoggedUser()
        );
    }

    #[Test]
    public function getLoggedUserTokenWithoutUser(): void
    {
        $security = $this->createMock(Security::class);
        $security
            ->expects($this->once())
            ->method('getUser')
            ->willReturn(null)
        ;

        $service = new UserTokenService($security);

        $this->expectException(NoLoggedUserException::class);
        $this->expectExceptionMessageIsOrContains('No user logged');

        $service->getLoggedUser();
    }
}
