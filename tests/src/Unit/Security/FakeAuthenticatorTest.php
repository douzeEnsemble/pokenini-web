<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security;

use App\Security\FakeAuthenticator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;

/**
 * @internal
 */
#[CoversClass(FakeAuthenticator::class)]
final class FakeAuthenticatorTest extends TestCase
{
    public function testSupports(): void
    {
        $authenticator = new FakeAuthenticator(
            $this->createStub(RouterInterface::class),
        );

        $this->assertTrue(
            $authenticator->supports(
                new Request([], [], ['_route' => 'app_connect_fake_check'])
            )
        );
        $this->assertFalse(
            $authenticator->supports(
                new Request([], [], ['_route' => 'app_connect_google_check'])
            )
        );
    }
}
