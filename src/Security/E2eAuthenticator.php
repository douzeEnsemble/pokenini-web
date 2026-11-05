<?php

declare(strict_types=1);

namespace App\Security;

use League\OAuth2\Client\Token\AccessToken;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

/**
 * Signs the E2E tests in as the test trainer, without Google: the secret sent in the X-E2E-Login header
 * is checked by pokenini-back (E2E_LOGIN_SECRET), which answers with the test trainer and its session token.
 */
final class E2eAuthenticator extends AbstractAuthenticator
{
    public const string HEADER = 'X-E2E-Login';

    #[\Override]
    public function authenticate(Request $request): Passport
    {
        $secret = (string) $request->headers->get(self::HEADER);

        if ('' === $secret) {
            throw new BadCredentialsException('Missing E2E login.');
        }

        $accessToken = new AccessToken([
            'access_token' => $secret,
        ]);

        return new SelfValidatingPassport(
            new UserBadge($this->getProviderCode(), function () use ($accessToken) {
                return $this->loadFromAccessToken($accessToken, $this->getProviderName());
            })
        );
    }

    #[\Override]
    protected function getProviderCode(): string
    {
        return 'e2e';
    }

    #[\Override]
    protected function getProviderName(): string
    {
        return 'e2e';
    }
}
