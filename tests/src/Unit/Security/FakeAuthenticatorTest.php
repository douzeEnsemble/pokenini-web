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
class FakeAuthenticatorTest extends TestCase
{
    public function testSupports(): void
    {
        $router = $this->createMock(RouterInterface::class);

        $authenticator = new FakeAuthenticator(
            $router,
            'listAdmin',
            'listTrainer',
            'listCollector',
            true,
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
