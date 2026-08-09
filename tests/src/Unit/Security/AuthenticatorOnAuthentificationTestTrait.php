<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security;

use App\Security\User;
use App\Service\Back\GetUserInfoService;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Security\Authenticator\OAuth2Authenticator;
use League\OAuth2\Client\Token\AccessToken;
use PHPUnit\Framework\Attributes\Test;
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
trait AuthenticatorOnAuthentificationTestTrait
{
    #[Test]
    public function onAuthenticationSuccessNotATrainer(): void
    {
        $user = new User(
            '1',
            'TestProvider',
            new AccessToken(['access_token' => 'zdazdzad-token-dazga']),
            'test-session-token',
        );

        $token = $this->createMock(TokenInterface::class);
        $token
            ->expects($this->once())
            ->method('getUser')
            ->willReturn($user)
        ;

        $authenticator = $this->getOnAuthenticationAuthenticator([
            '/success-but-not-a-trainer',
        ]);

        $session = new Session(new MockArraySessionStorage());
        $request = new Request();
        $request->setSession($session);

        $response = $authenticator->onAuthenticationSuccess($request, $token, 'web');

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
            new AccessToken(['access_token' => 'zdazdzad-token-dazga']),
            'test-session-token',
        );
        $user->addTrainerRole();

        $token = $this->createMock(TokenInterface::class);
        $token
            ->expects($this->once())
            ->method('getUser')
            ->willReturn($user)
        ;

        $authenticator = $this->getOnAuthenticationAuthenticator([
            '/success-but-not-a-trainer',
            '/success-trainer',
        ]);

        $session = new Session(new MockArraySessionStorage());
        $request = new Request();
        $request->setSession($session);

        $response = $authenticator->onAuthenticationSuccess($request, $token, 'web');

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
            new AccessToken(['access_token' => 'zdazdzad-token-dazga']),
            'test-session-token',
        );

        $token = $this->createMock(TokenInterface::class);
        $token
            ->expects($this->once())
            ->method('getUser')
            ->willReturn($user)
        ;

        $authenticator = $this->getOnAuthenticationAuthenticator([]);

        $session = new Session(new MockArraySessionStorage());
        $session->set('_security_target_path', '/fr/some-page');
        $request = new Request();
        $request->setSession($session);

        $response = $authenticator->onAuthenticationSuccess($request, $token, 'web');

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('/fr/some-page', $response->getTargetUrl());
        $this->assertNull($session->get('_security_target_path'));
        $this->assertSame('TestProvider', $session->get('_security_provider'));
    }

    #[Test]
    public function onAuthenticationFailure(): void
    {
        $authenticator = $this->getOnAuthenticationAuthenticator([]);

        $response = $authenticator->onAuthenticationFailure(
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
    private function getOnAuthenticationAuthenticator(
        array $routes = []
    ): OAuth2Authenticator {
        $router = $this->createMock(RouterInterface::class);
        $router
            ->expects($this->exactly(count($routes)))
            ->method('generate')
            ->willReturnOnConsecutiveCalls(...$routes)
        ;

        $clientRegistry = $this->createStub(ClientRegistry::class);
        $getUserInfoService = $this->createStub(GetUserInfoService::class);

        /** @var OAuth2Authenticator */
        return $this->getAuthenticatorInstance(
            $clientRegistry,
            $router,
            $getUserInfoService,
        );
    }
}
