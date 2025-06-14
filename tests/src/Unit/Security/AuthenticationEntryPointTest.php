<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security;

use App\Security\AuthenticationEntryPoint;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;

/**
 * @internal
 */
#[CoversClass(AuthenticationEntryPoint::class)]
class AuthenticationEntryPointTest extends TestCase
{
    public function testStart(): void
    {
        $router = $this->createMock(RouterInterface::class);
        $router
            ->expects($this->once())
            ->method('generate')
            ->with('app_home_index')
            ->willReturn('/home')
        ;

        $authenticator = new AuthenticationEntryPoint(
            $router,
        );

        $request = new Request();

        /** @var RedirectResponse $response */
        $response = $authenticator->start($request);

        $this->assertEquals('/home', $response->getTargetUrl());
    }
}
