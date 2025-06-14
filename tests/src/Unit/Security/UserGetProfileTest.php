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
class UserGetProfileTest extends TestCase
{
    public function testGetProfileAsDefault(): void
    {
        $user = new User('12', 'TestProvider');

        $this->assertEquals('user', $user->getProfile());
    }

    public function testGetProfileAsTrainer(): void
    {
        $user = new User('12', 'TestProvider');
        $user->addTrainerRole();

        $this->assertEquals('trainer', $user->getProfile());
    }

    public function testGetProfileAsCollector(): void
    {
        $user = new User('12', 'TestProvider');
        $user->addCollectorRole();

        $this->assertEquals('collector', $user->getProfile());
    }

    public function testGetProfileAsAdmin(): void
    {
        $user = new User('12', 'TestProvider');
        $user->addAdminRole();

        $this->assertEquals('admin', $user->getProfile());
    }

    public function testGetProfileAsTrainerAndAdmin(): void
    {
        $user = new User('12', 'TestProvider');
        $user->addTrainerRole();
        $user->addAdminRole();

        $this->assertEquals('admin', $user->getProfile());
    }

    public function testGetProfileAsCollectorAndAdmin(): void
    {
        $user = new User('12', 'TestProvider');
        $user->addCollectorRole();
        $user->addAdminRole();

        $this->assertEquals('admin', $user->getProfile());
    }

    public function testGetProfileAsTrainerAndCollector(): void
    {
        $user = new User('12', 'TestProvider');
        $user->addTrainerRole();
        $user->addCollectorRole();

        $this->assertEquals('collector', $user->getProfile());
    }
}
