<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security;

use App\Security\User;
use PHPUnit\Framework\TestCase;

class UserTest extends TestCase
{
    public function testConstructor(): void
    {
        $user = new User('12');

        $this->assertEquals('12', $user->getUserIdentifier());
        $this->assertEquals('12', $user->getId());
        $this->assertEquals(['ROLE_USER'], $user->getRoles());
    }

    public function testAddAdminRole(): void
    {
        $user = new User('12');
        $this->assertEquals(['ROLE_USER'], $user->getRoles());

        $user->addAdminRole();

        $this->assertEquals(['ROLE_USER', 'ROLE_ADMIN'], $user->getRoles());
    }

    public function testAddTrainerRole(): void
    {
        $user = new User('12');
        $this->assertEquals(['ROLE_USER'], $user->getRoles());

        $user->addTrainerRole();

        $this->assertEquals(['ROLE_USER', 'ROLE_TRAINER'], $user->getRoles());
    }

    public function testAddTrainerAndAdminRole(): void
    {
        $user = new User('12');
        $this->assertEquals(['ROLE_USER'], $user->getRoles());

        $user->addTrainerRole();
        $user->addAdminRole();

        $this->assertEquals(['ROLE_USER', 'ROLE_TRAINER', 'ROLE_ADMIN'], $user->getRoles());
    }

    public function testIsATrainer(): void
    {
        $user = new User('12');

        $this->assertFalse($user->isATrainer());

        $user->addTrainerRole();

        $this->assertTrue($user->isATrainer());
    }

    public function testIsAnAdmin(): void
    {
        $user = new User('12');

        $this->assertFalse($user->isAnAdmin());

        $user->addAdminRole();

        $this->assertTrue($user->isAnAdmin());
    }

    public function testIsATrainerAndAnAdmin(): void
    {
        $user = new User('12');

        $this->assertFalse($user->isATrainer());
        $this->assertFalse($user->isAnAdmin());

        $user->addTrainerRole();
        $user->addAdminRole();

        $this->assertTrue($user->isATrainer());
        $this->assertTrue($user->isAnAdmin());
    }
}
