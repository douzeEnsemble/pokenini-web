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
final class UserTest extends TestCase
{
    #[Test]
    public function constructor(): void
    {
        $user = new User(
            '12',
            'TestProvider',
            new AccessToken(['access_token' => 'zdazdazd564']),
        );

        $this->assertEquals('12', $user->getUserIdentifier());
        $this->assertEquals('12', $user->getId());
        $this->assertEquals('TestProvider', $user->getProviderName());
        $this->assertEquals(['ROLE_USER'], $user->getRoles());
    }

    #[Test]
    public function getAccessToken(): void
    {
        $user = new User(
            '12',
            'TestProvider',
            new AccessToken(['access_token' => 'zdazdazd564']),
        );

        $this->assertSame('zdazdazd564', $user->getAccessToken()->getToken());
    }
}
