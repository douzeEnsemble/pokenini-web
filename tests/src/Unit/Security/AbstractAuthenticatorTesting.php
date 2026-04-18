<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security;

use App\Service\Back\GetUserInfoService;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Security\Authenticator\OAuth2Authenticator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\RouterInterface;

/**
 * @internal
 */
abstract class AbstractAuthenticatorTesting extends TestCase
{
    use AuthenticatorSupportTestTrait;
    use AuthenticatorAuthenticateClosedTestTrait;
    use AuthenticatorAuthenticateOpenedTestTrait;
    use AuthenticatorOnAuthentificationTestTrait;

    abstract protected function getAuthenticatorInstance(
        ClientRegistry $clientRegistry,
        RouterInterface $router,
        GetUserInfoService $getUserInfoService,
    ): OAuth2Authenticator;

    abstract protected function getAuthenticatorProviderCode(): string;

    abstract protected function getAuthenticatorProviderName(): string;
}
