<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security;

use App\Security\AuthenticationEntryPoint;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Routing\RouterInterface;

/**
 * @internal
 */
#[CoversClass(AuthenticationEntryPoint::class)]
final class AuthenticationEntryPointTest extends TestCase
{
    #[Test]
    public function startWithNoProvider(): void
    {
        $router = $this->createMock(RouterInterface::class);
        $router
            ->expects($this->once())
            ->method('generate')
            ->with('app_home_index')
            ->willReturn('/home')
        ;

        $response = (new AuthenticationEntryPoint($router))->start($this->makeRequest());

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('/home', $response->getTargetUrl());
    }

    #[Test]
    public function startWithGoogleProviderRedirectsToGoogleAndSavesTargetPath(): void
    {
        $router = $this->createMock(RouterInterface::class);
        $router
            ->expects($this->once())
            ->method('generate')
            ->with('app_connect_google_goto')
            ->willReturn('/connect/g')
        ;

        $request = $this->makeRequest('google');
        $response = (new AuthenticationEntryPoint($router))->start($request);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('/connect/g', $response->getTargetUrl());
        $this->assertSame('http://localhost/fr/some-page', $request->getSession()->get('_security_target_path'));
    }

    #[Test]
    public function startWithDiscordProviderRedirectsToDiscordAndSavesTargetPath(): void
    {
        $router = $this->createMock(RouterInterface::class);
        $router
            ->expects($this->once())
            ->method('generate')
            ->with('app_connect_discord_goto')
            ->willReturn('/connect/dd')
        ;

        $request = $this->makeRequest('discord');
        $response = (new AuthenticationEntryPoint($router))->start($request);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('/connect/dd', $response->getTargetUrl());
        $this->assertSame('http://localhost/fr/some-page', $request->getSession()->get('_security_target_path'));
    }

    #[Test]
    public function startWithUnknownProviderFallsBackToHome(): void
    {
        $router = $this->createMock(RouterInterface::class);
        $router
            ->expects($this->once())
            ->method('generate')
            ->with('app_home_index')
            ->willReturn('/home')
        ;

        $response = (new AuthenticationEntryPoint($router))->start($this->makeRequest('unknown'));

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('/home', $response->getTargetUrl());
    }

    private function makeRequest(?string $provider = null): Request
    {
        $session = new Session(new MockArraySessionStorage());
        if (null !== $provider) {
            $session->set('_security_provider', $provider);
        }
        $request = Request::create('http://localhost/fr/some-page');
        $request->setSession($session);

        return $request;
    }
}
