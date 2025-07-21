<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security;

use App\Service\Back\GetUserInfoService;
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

        $getUserInfoService = $this->createMock(GetUserInfoService::class);

        /** @var OAuth2Authenticator $authenticator */
        $authenticator = new ($this->getAuthenticatorClassName())(
            $clientRegistry,
            $router,
            $getUserInfoService,
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
