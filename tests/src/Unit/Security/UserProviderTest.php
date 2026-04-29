<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security;

use App\Security\User;
use App\Security\UserProvider;
use App\Security\UserRefresher;
use League\OAuth2\Client\Token\AccessToken;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @internal
 */
#[CoversClass(UserProvider::class)]
final class UserProviderTest extends TestCase
{
    public function testLoadUserByIdentifier(): void
    {
        $refresher = $this->createMock(UserRefresher::class);
        $refresher
            ->expects($this->never())
            ->method('refresh')
        ;

        $provider = new UserProvider($refresher);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Not use in this project');

        $provider->loadUserByIdentifier('douze');
    }

    public function testRefreshUser(): void
    {
        $user = new User(
            'douze',
            'TestProvider',
            new AccessToken([
                'access_token' => 'dzadzz',
                'expires_in' => -1,
            ])
        );

        $refresher = $this->createMock(UserRefresher::class);
        $refresher
            ->expects($this->once())
            ->method('refresh')
            ->with($user)
            ->willReturn($user)
        ;

        $provider = new UserProvider($refresher);

        $provider->refreshUser($user);
    }

    public function testRefreshUserWrongUser(): void
    {
        $refresher = $this->createMock(UserRefresher::class);
        $refresher
            ->expects($this->never())
            ->method('refresh')
        ;

        $provider = new UserProvider($refresher);

        $this->expectException(UnsupportedUserException::class);
        $this->expectExceptionMessageMatches('/Invalid user class "TestStub_UserInterface_.{8}"\./');

        $notUser = $this->createStub(UserInterface::class);

        $provider->refreshUser($notUser);
    }

    public function testUpgradePassword(): void
    {
        $refresher = $this->createMock(UserRefresher::class);
        $refresher
            ->expects($this->never())
            ->method('refresh')
        ;

        $provider = new UserProvider($refresher);

        $user = $initialUser = $this->createStub(PasswordAuthenticatedUserInterface::class);

        $provider->upgradePassword($user, 'e3ca7fbe759a0d0afb2cbd2a62390472');

        $this->assertSame($initialUser, $user);
    }

    public function testSupportsClass(): void
    {
        $refresher = $this->createMock(UserRefresher::class);
        $refresher
            ->expects($this->never())
            ->method('refresh')
        ;

        $provider = new UserProvider($refresher);

        $this->assertTrue($provider->supportsClass('App\Security\User'));
        $this->assertFalse($provider->supportsClass('App\Entity\User'));
    }
}
