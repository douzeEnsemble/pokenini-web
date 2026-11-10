<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security;

use App\Security\User;
use League\OAuth2\Client\Token\AccessToken;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(User::class)]
final class UserGetProfileTest extends TestCase
{
    #[Test]
    public function getProfileAsDefault(): void
    {
        $user = new User(
            '12',
            'TestProvider',
            new AccessToken(['access_token' => 'dzzad']),
            'test-session-token',
        );

        $this->assertSame('user', $user->getProfile());
    }

    #[Test]
    public function getProfileAsTrainer(): void
    {
        $user = new User(
            '12',
            'TestProvider',
            new AccessToken(['access_token' => 'dzzad']),
            'test-session-token',
        );
        $user->addTrainerRole();

        $this->assertSame('trainer', $user->getProfile());
    }

    #[Test]
    public function getProfileAsCollector(): void
    {
        $user = new User(
            '12',
            'TestProvider',
            new AccessToken(['access_token' => 'dzzad']),
            'test-session-token',
        );
        $user->addCollectorRole();

        $this->assertSame('collector', $user->getProfile());
    }

    #[Test]
    public function getProfileAsAdmin(): void
    {
        $user = new User(
            '12',
            'TestProvider',
            new AccessToken(['access_token' => 'dzzad']),
            'test-session-token',
        );
        $user->addAdminRole();

        $this->assertSame('admin', $user->getProfile());
    }

    #[Test]
    public function getProfileAsTrainerAndAdmin(): void
    {
        $user = new User(
            '12',
            'TestProvider',
            new AccessToken(['access_token' => 'dzzad']),
            'test-session-token',
        );
        $user->addTrainerRole();
        $user->addAdminRole();

        $this->assertSame('admin', $user->getProfile());
    }

    #[Test]
    public function getProfileAsCollectorAndAdmin(): void
    {
        $user = new User(
            '12',
            'TestProvider',
            new AccessToken(['access_token' => 'dzzad']),
            'test-session-token',
        );
        $user->addCollectorRole();
        $user->addAdminRole();

        $this->assertSame('admin', $user->getProfile());
    }

    #[Test]
    public function getProfileAsTrainerAndCollector(): void
    {
        $user = new User(
            '12',
            'TestProvider',
            new AccessToken(['access_token' => 'dzzad']),
            'test-session-token',
        );
        $user->addTrainerRole();
        $user->addCollectorRole();

        $this->assertSame('collector', $user->getProfile());
    }
}
