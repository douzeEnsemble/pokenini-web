<?php

declare(strict_types=1);

namespace App\Security;

use App\Service\Back\GetUserInfoService;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Security\Authenticator\OAuth2Authenticator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

abstract class AbstractAuthenticator extends OAuth2Authenticator
{
    use AuthenticatorTrait;

    public function __construct(
        private readonly ClientRegistry $clientRegistry,
        private readonly RouterInterface $router,
        private readonly GetUserInfoService $getUserInfoService,
    ) {}

    #[\Override]
    public function supports(Request $request): ?bool
    {
        return 'app_connect_'.$this->getProviderCode().'_check' === $request->attributes->get('_route');
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    #[\Override]
    public function authenticate(Request $request): Passport
    {
        $client = $this->clientRegistry->getClient($this->getProviderCode());
        $accessToken = $this->fetchAccessToken($client);

        return new SelfValidatingPassport(
            new UserBadge($accessToken->getToken(), function () use ($accessToken) {
                return $this->loadFromAccessToken($accessToken, $this->getProviderName());
            })
        );
    }

    abstract protected function getProviderCode(): string;

    abstract protected function getProviderName(): string;
}
