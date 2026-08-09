<?php

declare(strict_types=1);

namespace App\Security;

use KnpU\OAuth2ClientBundle\Security\Authenticator\OAuth2Authenticator;
use League\OAuth2\Client\Token\AccessToken;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

final class FakeAuthenticator extends OAuth2Authenticator
{
    use AuthenticatorTrait;

    public function __construct(
        private readonly RouterInterface $router,
    ) {}

    /**
     * @phpstan-ignore return.unusedType
     */
    #[\Override]
    public function supports(Request $request): ?bool
    {
        return 'app_connect_fake_check' === $request->attributes->get('_route');
    }

    /**
     * @SuppressWarnings("PHPMD.UnusedFormalParameter")
     */
    #[\Override]
    public function authenticate(Request $request): Passport
    {
        $identifier = $request->query->getString('t');

        // expires is set to PHP_INT_MAX so dev sessions never expire and
        // UserRefresher never attempts an OAuth refresh for the fake provider.
        $accessToken = new AccessToken([
            'access_token' => $identifier,
            'expires' => PHP_INT_MAX,
        ]);

        return new SelfValidatingPassport(
            new UserBadge($identifier, function () use ($accessToken, $identifier): User {
                return $this->buildUser($accessToken, $identifier);
            })
        );
    }

    private function buildUser(AccessToken $accessToken, string $identifier): User
    {
        // Dev-only shortcut: no call to pokenini-back's /user exchange endpoint here, so the
        // session token is simply the identifier itself — pokenini-back's AccessTokenHandler
        // falls back to its own "fake" provider handling for any bearer token that isn't a
        // valid internal JWT, exactly like it does for the raw provider token today.
        $user = new User($identifier, 'fake', $accessToken, $identifier);

        if ('admin' === $identifier) {
            $user->addAdminRole();
            $user->addCollectorRole();
            $user->addTrainerRole();
        } elseif ('collector' === $identifier) {
            $user->addCollectorRole();
            $user->addTrainerRole();
        } elseif ('trainer' === $identifier) {
            $user->addTrainerRole();
        }

        return $user;
    }
}
