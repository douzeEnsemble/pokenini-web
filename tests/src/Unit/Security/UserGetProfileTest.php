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
            new AccessToken(['access_token' => 'dzzad'])
        );

        $this->assertEquals('user', $user->getProfile());
    }

    #[Test]
    public function getProfileAsTrainer(): void
    {
        $user = new User(
            '12',
            'TestProvider',
            new AccessToken(['access_token' => 'dzzad'])
        );
        $user->addTrainerRole();

        $this->assertEquals('trainer', $user->getProfile());
    }

    #[Test]
    public function getProfileAsCollector(): void
    {
        $user = new User(
            '12',
            'TestProvider',
            new AccessToken(['access_token' => 'dzzad'])
        );
        $user->addCollectorRole();

        $this->assertEquals('collector', $user->getProfile());
    }

    #[Test]
    public function getProfileAsAdmin(): void
    {
        $user = new User(
            '12',
            'TestProvider',
            new AccessToken(['access_token' => 'dzzad'])
        );
        $user->addAdminRole();

        $this->assertEquals('admin', $user->getProfile());
    }

    #[Test]
    public function getProfileAsTrainerAndAdmin(): void
    {
        $user = new User(
            '12',
            'TestProvider',
            new AccessToken(['access_token' => 'dzzad'])
        );
        $user->addTrainerRole();
        $user->addAdminRole();

        $this->assertEquals('admin', $user->getProfile());
    }

    #[Test]
    public function getProfileAsCollectorAndAdmin(): void
    {
        $user = new User(
            '12',
            'TestProvider',
            new AccessToken(['access_token' => 'dzzad'])
        );
        $user->addCollectorRole();
        $user->addAdminRole();

        $this->assertEquals('admin', $user->getProfile());
    }

    #[Test]
    public function getProfileAsTrainerAndCollector(): void
    {
        $user = new User(
            '12',
            'TestProvider',
            new AccessToken(['access_token' => 'dzzad'])
        );
        $user->addTrainerRole();
        $user->addCollectorRole();

        $this->assertEquals('collector', $user->getProfile());
    }
}
