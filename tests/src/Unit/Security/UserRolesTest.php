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
final class UserRolesTest extends TestCase
{
    #[Test]
    public function addAdminRole(): void
    {
        $user = new User(
            '12',
            'TestProvider',
            new AccessToken(['access_token' => 'zdazdazd564']),
        );
        $this->assertEquals(['ROLE_USER'], $user->getRoles());

        $user->addAdminRole();

        $this->assertEquals(['ROLE_USER', 'ROLE_ADMIN'], $user->getRoles());
    }

    #[Test]
    public function addTrainerRole(): void
    {
        $user = new User(
            '12',
            'TestProvider',
            new AccessToken(['access_token' => 'zdazdazd564']),
        );
        $this->assertEquals(['ROLE_USER'], $user->getRoles());

        $user->addTrainerRole();

        $this->assertEquals(['ROLE_USER', 'ROLE_TRAINER'], $user->getRoles());
    }

    #[Test]
    public function addCollectorRole(): void
    {
        $user = new User(
            '12',
            'TestProvider',
            new AccessToken(['access_token' => 'zdazdazd564']),
        );
        $this->assertEquals(['ROLE_USER'], $user->getRoles());

        $user->addCollectorRole();

        $this->assertEquals(['ROLE_USER', 'ROLE_COLLECTOR'], $user->getRoles());
    }

    #[Test]
    public function addTrainerAndAdminRole(): void
    {
        $user = new User(
            '12',
            'TestProvider',
            new AccessToken(['access_token' => 'zdazdazd564']),
        );
        $this->assertEquals(['ROLE_USER'], $user->getRoles());

        $user->addTrainerRole();
        $user->addAdminRole();

        $this->assertEquals(['ROLE_USER', 'ROLE_TRAINER', 'ROLE_ADMIN'], $user->getRoles());
    }

    #[Test]
    public function addTrainerAndCollectorRole(): void
    {
        $user = new User(
            '12',
            'TestProvider',
            new AccessToken(['access_token' => 'zdazdazd564']),
        );
        $this->assertEquals(['ROLE_USER'], $user->getRoles());

        $user->addTrainerRole();
        $user->addCollectorRole();

        $this->assertEquals(['ROLE_USER', 'ROLE_TRAINER', 'ROLE_COLLECTOR'], $user->getRoles());
    }

    #[Test]
    public function addTrainerRoleTwice(): void
    {
        $user = new User(
            '12',
            'TestProvider',
            new AccessToken(['access_token' => 'zdazdazd564']),
        );
        $this->assertEquals(['ROLE_USER'], $user->getRoles());

        $user->addTrainerRole();
        $user->addTrainerRole();

        $this->assertEquals(['ROLE_USER', 'ROLE_TRAINER'], $user->getRoles());
    }

    #[Test]
    public function addAdminRoleTwice(): void
    {
        $user = new User(
            '12',
            'TestProvider',
            new AccessToken(['access_token' => 'zdazdazd564']),
        );
        $this->assertEquals(['ROLE_USER'], $user->getRoles());

        $user->addAdminRole();
        $user->addAdminRole();

        $this->assertEquals(['ROLE_USER', 'ROLE_ADMIN'], $user->getRoles());
    }

    #[Test]
    public function addCollectorRoleTwice(): void
    {
        $user = new User(
            '12',
            'TestProvider',
            new AccessToken(['access_token' => 'zdazdazd564']),
        );
        $this->assertEquals(['ROLE_USER'], $user->getRoles());

        $user->addCollectorRole();
        $user->addCollectorRole();

        $this->assertEquals(['ROLE_USER', 'ROLE_COLLECTOR'], $user->getRoles());
    }

    #[Test]
    public function isATrainer(): void
    {
        $user = new User(
            '12',
            'TestProvider',
            new AccessToken(['access_token' => 'zdazdazd564']),
        );

        $this->assertFalse($user->isATrainer());

        $user->addTrainerRole();

        $this->assertTrue($user->isATrainer());
    }

    #[Test]
    public function isACollector(): void
    {
        $user = new User(
            '12',
            'TestProvider',
            new AccessToken(['access_token' => 'zdazdazd564']),
        );

        $this->assertFalse($user->isACollector());

        $user->addCollectorRole();

        $this->assertTrue($user->isACollector());
    }

    #[Test]
    public function isAnAdmin(): void
    {
        $user = new User(
            '12',
            'TestProvider',
            new AccessToken(['access_token' => 'zdazdazd564']),
        );

        $this->assertFalse($user->isAnAdmin());

        $user->addAdminRole();

        $this->assertTrue($user->isAnAdmin());
    }

    #[Test]
    public function isATrainerAndAnAdmin(): void
    {
        $user = new User(
            '12',
            'TestProvider',
            new AccessToken(['access_token' => 'zdazdazd564']),
        );

        $this->assertFalse($user->isATrainer());
        $this->assertFalse($user->isAnAdmin());
        $this->assertFalse($user->isACollector());

        $user->addTrainerRole();
        $user->addAdminRole();

        $this->assertTrue($user->isATrainer());
        $this->assertTrue($user->isAnAdmin());
        $this->assertFalse($user->isACollector());
    }

    #[Test]
    public function isATrainerAndACollector(): void
    {
        $user = new User(
            '12',
            'TestProvider',
            new AccessToken(['access_token' => 'zdazdazd564']),
        );

        $this->assertFalse($user->isATrainer());
        $this->assertFalse($user->isACollector());
        $this->assertFalse($user->isAnAdmin());

        $user->addTrainerRole();
        $user->addCollectorRole();

        $this->assertTrue($user->isATrainer());
        $this->assertTrue($user->isACollector());
        $this->assertFalse($user->isAnAdmin());
    }
}
