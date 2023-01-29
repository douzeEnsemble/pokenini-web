<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security;

use App\Security\User;
use App\Security\UserProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class UserProviderTest extends TestCase
{
    public function testLoadUserByIdentifier(): void
    {
        $provider = new UserProvider();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Not use in this project');

        $provider->loadUserByIdentifier('douze');
    }

    public function testRefreshUser(): void
    {
        $provider = new UserProvider();

        $user = new User('douze');

        $freshUser = $provider->refreshUser($user);

        $this->assertSame($user, $freshUser);
    }

    public function testRefreshUserWrongUser(): void
    {
        $provider = new UserProvider();

        $this->expectException(UnsupportedUserException::class);
        $this->expectExceptionMessageMatches('/Invalid user class "Mock_UserInterface_.{8}"\./');

        $notUser = $this->createMock(UserInterface::class);

        $provider->refreshUser($notUser);
    }

    public function testUpgradePassword(): void
    {
        $provider = new UserProvider();

        $user = $initialUser = $this->createMock(PasswordAuthenticatedUserInterface::class);

        $provider->upgradePassword($user, 'e3ca7fbe759a0d0afb2cbd2a62390472');

        $this->assertSame($initialUser, $user);
    }
}
