<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security;

use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Security\Authenticator\OAuth2Authenticator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;

/**
 * @internal
 */
trait AuthenticatorSupportTestTrait
{
    public function testSupports(): void
    {
        $clientRegistry = $this->createMock(ClientRegistry::class);

        $router = $this->createMock(RouterInterface::class);

        /** @var OAuth2Authenticator $authenticator */
        $authenticator = new ($this->getAuthenticatorClassName())(
            $clientRegistry,
            $router,
            'listAdmin',
            'listTrainer',
            'listCollector',
            true,
        );

        $this->assertTrue(
            $authenticator->supports(
                new Request([], [], ['_route' => 'app_connect_'.$this->getAuthenticatorProviderCode().'_check'])
            )
        );
        $this->assertFalse(
            $authenticator->supports(
                new Request([], [], ['_route' => 'app_connect_check'])
            )
        );
    }
}
