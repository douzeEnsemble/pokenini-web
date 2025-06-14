<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security;

use App\Security\User;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Security\Authenticator\OAuth2Authenticator;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

/**
 * @internal
 */
trait AuthenticatorOnAuthentificationTestTrait
{
    public function testOnAuthenticationSuccessNotATrainer(): void
    {
        $user = new User('1', 'TestProvider');

        $token = $this->createMock(TokenInterface::class);
        $token
            ->expects($this->once())
            ->method('getUser')
            ->willReturn($user)
        ;

        $authenticator = $this->getOnAuthenticationAuthenticator([
            '/success-but-not-a-trainer',
        ]);

        $response = $authenticator->onAuthenticationSuccess(
            $this->createMock(Request::class),
            $token,
            'web'
        );

        $this->assertInstanceOf(RedirectResponse::class, $response);

        // @var $response RedirectResponse
        $this->assertEquals('/success-but-not-a-trainer', $response->getTargetUrl());
    }

    public function testOnAuthenticationSuccessTrainer(): void
    {
        $user = new User('1', 'TestProvider');
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

        $response = $authenticator->onAuthenticationSuccess(
            $this->createMock(Request::class),
            $token,
            'web'
        );

        $this->assertInstanceOf(RedirectResponse::class, $response);

        // @var $response RedirectResponse
        $this->assertEquals('/success-trainer', $response->getTargetUrl());
    }

    public function testOnAuthenticationFailure(): void
    {
        $authenticator = $this->getOnAuthenticationAuthenticator([]);

        $response = $authenticator->onAuthenticationFailure(
            $this->createMock(Request::class),
            new AuthenticationException()
        );

        $this->assertInstanceOf(Response::class, $response);

        $this->assertEquals(403, $response->getStatusCode());
        $this->assertEquals('An authentication exception occurred.', $response->getContent());
    }

    /**
     * @param string[] $routes
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

        /** @var OAuth2Authenticator */
        return new ($this->getAuthenticatorClassName())(
            $this->createMock(ClientRegistry::class),
            $router,
            '',
            '',
            '',
            true,
        );
    }
}
