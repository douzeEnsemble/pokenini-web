<?php

declare(strict_types=1);

namespace App\Security;

use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use League\OAuth2\Client\Token\AccessToken;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationExpiredException;

class UserRefresher
{
    public function __construct(
        private readonly ClientRegistry $clientRegistry,
        private readonly LoggerInterface $logger,
    ) {}

    public function refresh(User $user): User
    {
        $accessToken = $user->getAccessToken();

        if (!$accessToken->hasExpired()) {
            return $user;
        }

        $refreshToken = $accessToken->getRefreshToken();

        if (null === $refreshToken) {
            throw new AuthenticationExpiredException('Access token expired and no refresh token available.');
        }

        try {
            $client = $this->clientRegistry->getClient(strtolower($user->getProviderName()));
            $provider = $client->getOAuth2Provider();

            /**
             * @var AccessToken
             */
            $newAccessToken = $provider->getAccessToken(
                'refresh_token',
                [
                    'refresh_token' => $refreshToken,
                ]
            );
        } catch (\Throwable $exception) {
            $this->logger->warning(
                'Failed to refresh OAuth2 access token: {message}',
                [
                    'message' => $exception->getMessage(),
                    'provider' => $user->getProviderName(),
                    'exception' => $exception,
                ]
            );

            throw new AuthenticationExpiredException('Access token refresh failed.', previous: $exception);
        }

        // Providers (e.g. Google) don't always return a new refresh token on each refresh —
        // carry the old one forward so the next expiry can still be refreshed.
        if (null === $newAccessToken->getRefreshToken()) {
            $newAccessToken = new AccessToken([
                'access_token' => $newAccessToken->getToken(),
                'refresh_token' => $refreshToken,
                'expires' => $newAccessToken->getExpires(),
            ]);
        }

        $newUser = new User(
            $user->getUserIdentifier(),
            $user->getProviderName(),
            $newAccessToken,
        );

        foreach ($user->getRoles() as $role) {
            match ($role) {
                'ROLE_ADMIN' => $newUser->addAdminRole(),
                'ROLE_TRAINER' => $newUser->addTrainerRole(),
                'ROLE_COLLECTOR' => $newUser->addCollectorRole(),
                default => null,
            };
        }

        return $newUser;
    }
}
