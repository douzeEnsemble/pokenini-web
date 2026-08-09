<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security;

use App\Security\User;
use App\Security\UserProvider;
use App\Security\UserRefresher;
use League\OAuth2\Client\Token\AccessToken;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
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
    #[Test]
    public function loadUserByIdentifier(): void
    {
        $refresher = $this->createMock(UserRefresher::class);
        $refresher
            ->expects($this->never())
            ->method('refresh')
        ;

        $provider = new UserProvider($refresher);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageIsOrContains('Not use in this project');

        $provider->loadUserByIdentifier('douze');
    }

    #[Test]
    public function refreshUser(): void
    {
        $user = new User(
            'douze',
            'TestProvider',
            new AccessToken([
                'access_token' => 'dzadzz',
                'expires_in' => -1,
            ]),
            'test-session-token',
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

    #[Test]
    public function refreshUserWrongUser(): void
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

    #[Test]
    public function upgradePassword(): void
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

    #[Test]
    public function supportsClass(): void
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
