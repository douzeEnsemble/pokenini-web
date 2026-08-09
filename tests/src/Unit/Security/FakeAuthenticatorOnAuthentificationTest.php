<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security;

use App\Security\FakeAuthenticator;
use App\Security\User;
use League\OAuth2\Client\Token\AccessToken;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

/**
 * @internal
 */
#[CoversClass(FakeAuthenticator::class)]
final class FakeAuthenticatorOnAuthentificationTest extends TestCase
{
    #[Test]
    public function onAuthenticationSuccessNotATrainer(): void
    {
        $user = new User(
            '1',
            'TestProvider',
            new AccessToken(['access_token' => 'dzad6a4d'])
        );

        $token = $this->createMock(TokenInterface::class);
        $token
            ->expects($this->once())
            ->method('getUser')
            ->willReturn($user)
        ;

        $fakeAuthenticator = $this->getFakeAuthenticator(['/success-but-not-a-trainer']);

        $session = new Session(new MockArraySessionStorage());
        $request = new Request();
        $request->setSession($session);

        $response = $fakeAuthenticator->onAuthenticationSuccess($request, $token, 'web');

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('/success-but-not-a-trainer', $response->getTargetUrl());
        $this->assertSame('TestProvider', $session->get('_security_provider'));
    }

    #[Test]
    public function onAuthenticationSuccessTrainer(): void
    {
        $user = new User(
            '1',
            'TestProvider',
            new AccessToken(['access_token' => 'dzad6a4d'])
        );
        $user->addTrainerRole();

        $token = $this->createMock(TokenInterface::class);
        $token
            ->expects($this->once())
            ->method('getUser')
            ->willReturn($user)
        ;

        $fakeAuthenticator = $this->getFakeAuthenticator([
            '/success-but-not-a-trainer',
            '/success-trainer',
        ]);

        $session = new Session(new MockArraySessionStorage());
        $request = new Request();
        $request->setSession($session);

        $response = $fakeAuthenticator->onAuthenticationSuccess($request, $token, 'web');

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('/success-trainer', $response->getTargetUrl());
        $this->assertSame('TestProvider', $session->get('_security_provider'));
    }

    #[Test]
    public function onAuthenticationSuccessWithTargetPath(): void
    {
        $user = new User(
            '1',
            'TestProvider',
            new AccessToken(['access_token' => 'dzad6a4d'])
        );

        $token = $this->createMock(TokenInterface::class);
        $token
            ->expects($this->once())
            ->method('getUser')
            ->willReturn($user)
        ;

        $fakeAuthenticator = $this->getFakeAuthenticator([]);

        $session = new Session(new MockArraySessionStorage());
        $session->set('_security_target_path', '/fr/some-page');
        $request = new Request();
        $request->setSession($session);

        $response = $fakeAuthenticator->onAuthenticationSuccess($request, $token, 'web');

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('/fr/some-page', $response->getTargetUrl());
        $this->assertNull($session->get('_security_target_path'));
        $this->assertSame('TestProvider', $session->get('_security_provider'));
    }

    #[Test]
    public function onAuthenticationFailure(): void
    {
        $fakeAuthenticator = $this->getFakeAuthenticator([]);

        $response = $fakeAuthenticator->onAuthenticationFailure(
            $this->createStub(Request::class),
            new AuthenticationException()
        );

        $this->assertInstanceOf(Response::class, $response);

        $this->assertEquals(403, $response->getStatusCode());
        $this->assertEquals('An authentication exception occurred.', $response->getContent());
    }

    /**
     * @param array<int, string> $routes
     */
    private function getFakeAuthenticator(array $routes = []): FakeAuthenticator
    {
        $router = $this->createMock(RouterInterface::class);
        $router
            ->expects($this->exactly(count($routes)))
            ->method('generate')
            ->willReturnOnConsecutiveCalls(...$routes)
        ;

        return new FakeAuthenticator($router);
    }
}
