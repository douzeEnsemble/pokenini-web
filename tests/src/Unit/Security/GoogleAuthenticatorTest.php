<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security;

use App\Security\GoogleAuthenticator;
use App\Service\Back\GetUserInfoService;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Security\Authenticator\OAuth2Authenticator;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\Routing\RouterInterface;

/**
 * @internal
 */
#[CoversClass(GoogleAuthenticator::class)]
final class GoogleAuthenticatorTest extends AbstractAuthenticatorTesting
{
    #[\Override]
    protected function getAuthenticatorInstance(
        ClientRegistry $clientRegistry,
        RouterInterface $router,
        GetUserInfoService $getUserInfoService,
    ): OAuth2Authenticator {
        return new GoogleAuthenticator($clientRegistry, $router, $getUserInfoService);
    }

    #[\Override]
    protected function getAuthenticatorProviderCode(): string
    {
        return 'google';
    }

    #[\Override]
    protected function getAuthenticatorProviderName(): string
    {
        return 'Google';
    }
}
