<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security;

use App\Service\Back\GetUserInfoService;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;

/**
 * @internal
 */
trait AuthenticatorSupportTestTrait
{
    public function testSupports(): void
    {
        $clientRegistry = $this->createStub(ClientRegistry::class);

        $router = $this->createStub(RouterInterface::class);

        $getUserInfoService = $this->createStub(GetUserInfoService::class);

        $authenticator = $this->getAuthenticatorInstance(
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
