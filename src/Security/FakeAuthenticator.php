<?php

declare(strict_types=1);

namespace App\Security;

use KnpU\OAuth2ClientBundle\Security\Authenticator\OAuth2Authenticator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

class FakeAuthenticator extends OAuth2Authenticator
{
    use AuthenticatorTrait;

    public function __construct(
        private readonly RouterInterface $router,
        private readonly string $listAdmin,
        private readonly string $listTrainer,
        private readonly string $listCollector,
        private readonly bool $isInvitationRequired,
    ) {}

    #[\Override]
    public function supports(Request $request): ?bool
    {
        return 'app_connect_fake_check' === $request->attributes->get('_route');
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    #[\Override]
    public function authenticate(Request $request): Passport
    {
        $identifier = $request->query->getString('t');

        return new SelfValidatingPassport(
            new UserBadge($identifier, function () use ($identifier) {
                return $this->loadUserFromLists($identifier, 'FaKe');
            })
        );
    }
}
