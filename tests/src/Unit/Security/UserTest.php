<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security;

use App\Security\User;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(User::class)]
class UserTest extends TestCase
{
    public function testConstructor(): void
    {
        $user = new User('12', 'TestProvider');

        $this->assertEquals('12', $user->getUserIdentifier());
        $this->assertEquals('12', $user->getId());
        $this->assertEquals('TestProvider', $user->getProviderName());
        $this->assertEquals(['ROLE_USER'], $user->getRoles());
    }

    public function testAddAdminRole(): void
    {
        $user = new User('12', 'TestProvider');
        $this->assertEquals(['ROLE_USER'], $user->getRoles());

        $user->addAdminRole();

        $this->assertEquals(['ROLE_USER', 'ROLE_ADMIN'], $user->getRoles());
    }

    public function testAddTrainerRole(): void
    {
        $user = new User('12', 'TestProvider');
        $this->assertEquals(['ROLE_USER'], $user->getRoles());

        $user->addTrainerRole();

        $this->assertEquals(['ROLE_USER', 'ROLE_TRAINER'], $user->getRoles());
    }

    public function testAddCollectorRole(): void
    {
        $user = new User('12', 'TestProvider');
        $this->assertEquals(['ROLE_USER'], $user->getRoles());

        $user->addCollectorRole();

        $this->assertEquals(['ROLE_USER', 'ROLE_COLLECTOR'], $user->getRoles());
    }

    public function testAddTrainerAndAdminRole(): void
    {
        $user = new User('12', 'TestProvider');
        $this->assertEquals(['ROLE_USER'], $user->getRoles());

        $user->addTrainerRole();
        $user->addAdminRole();

        $this->assertEquals(['ROLE_USER', 'ROLE_TRAINER', 'ROLE_ADMIN'], $user->getRoles());
    }

    public function testAddTrainerAndCollectorRole(): void
    {
        $user = new User('12', 'TestProvider');
        $this->assertEquals(['ROLE_USER'], $user->getRoles());

        $user->addTrainerRole();
        $user->addCollectorRole();

        $this->assertEquals(['ROLE_USER', 'ROLE_TRAINER', 'ROLE_COLLECTOR'], $user->getRoles());
    }

    public function testAddTrainerRoleTwice(): void
    {
        $user = new User('12', 'TestProvider');
        $this->assertEquals(['ROLE_USER'], $user->getRoles());

        $user->addTrainerRole();
        $user->addTrainerRole();

        $this->assertEquals(['ROLE_USER', 'ROLE_TRAINER'], $user->getRoles());
    }

    public function testAddAdminRoleTwice(): void
    {
        $user = new User('12', 'TestProvider');
        $this->assertEquals(['ROLE_USER'], $user->getRoles());

        $user->addAdminRole();
        $user->addAdminRole();

        $this->assertEquals(['ROLE_USER', 'ROLE_ADMIN'], $user->getRoles());
    }

    public function testAddCollectorRoleTwice(): void
    {
        $user = new User('12', 'TestProvider');
        $this->assertEquals(['ROLE_USER'], $user->getRoles());

        $user->addCollectorRole();
        $user->addCollectorRole();

        $this->assertEquals(['ROLE_USER', 'ROLE_COLLECTOR'], $user->getRoles());
    }

    public function testIsATrainer(): void
    {
        $user = new User('12', 'TestProvider');

        $this->assertFalse($user->isATrainer());

        $user->addTrainerRole();

        $this->assertTrue($user->isATrainer());
    }

    public function testIsACollector(): void
    {
        $user = new User('12', 'TestProvider');

        $this->assertFalse($user->isACollector());

        $user->addCollectorRole();

        $this->assertTrue($user->isACollector());
    }

    public function testIsAnAdmin(): void
    {
        $user = new User('12', 'TestProvider');

        $this->assertFalse($user->isAnAdmin());

        $user->addAdminRole();

        $this->assertTrue($user->isAnAdmin());
    }

    public function testIsATrainerAndAnAdmin(): void
    {
        $user = new User('12', 'TestProvider');

        $this->assertFalse($user->isATrainer());
        $this->assertFalse($user->isAnAdmin());
        $this->assertFalse($user->isACollector());

        $user->addTrainerRole();
        $user->addAdminRole();

        $this->assertTrue($user->isATrainer());
        $this->assertTrue($user->isAnAdmin());
        $this->assertFalse($user->isACollector());
    }

    public function testIsATrainerAndACollector(): void
    {
        $user = new User('12', 'TestProvider');

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
