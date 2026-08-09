<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security;

use App\Security\User;
use App\Security\UserRefresher;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Client\OAuth2ClientInterface;
use KnpU\OAuth2ClientBundle\DependencyInjection\InvalidOAuth2ClientException;
use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Token\AccessToken;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationExpiredException;

/**
 * @internal
 */
#[CoversClass(UserRefresher::class)]
final class UserRefresherTest extends TestCase
{
    #[Test]
    public function refreshExpiredTokenPreservesOldRefreshTokenWhenProviderOmitsIt(): void
    {
        $oauthProvider = $this->createMock(AbstractProvider::class);
        $expectedExpiry = time() + 3600;

        $oauthProvider
            ->expects($this->once())
            ->method('getAccessToken')
            ->with('refresh_token', ['refresh_token' => 'ipoadnaz'])
            ->willReturn(new AccessToken(['access_token' => 'yoiup', 'expires' => $expectedExpiry]))
        ;

        $client = $this->createMock(OAuth2ClientInterface::class);
        $client
            ->expects($this->once())
            ->method('getOAuth2Provider')
            ->with()
            ->willReturn($oauthProvider)
        ;

        $clientRegistry = $this->createMock(ClientRegistry::class);
        $clientRegistry
            ->expects($this->once())
            ->method('getClient')
            ->with('testprovider')
            ->willReturn($client)
        ;

        $refresher = new UserRefresher($clientRegistry, $this->createStub(LoggerInterface::class));

        $user = new User(
            'douze',
            'TestProvider',
            new AccessToken([
                'access_token' => 'dzadzz',
                'refresh_token' => 'ipoadnaz',
                'expires_in' => -1,
            ]),
            'test-session-token',
        );
        $user->addAdminRole();
        $user->addCollectorRole();
        $user->addTrainerRole();

        $newUser = $refresher->refresh($user);

        $this->assertSame($user->getUserIdentifier(), $newUser->getUserIdentifier());
        $this->assertSame($user->getProviderName(), $newUser->getProviderName());
        $this->assertSame($user->getRoles(), $newUser->getRoles());
        $this->assertSame('yoiup', $newUser->getAccessToken()->getToken());
        $this->assertSame('ipoadnaz', $newUser->getAccessToken()->getRefreshToken());
        $this->assertSame($expectedExpiry, $newUser->getAccessToken()->getExpires());
        $this->assertSame('test-session-token', $newUser->getSessionToken());
    }

    #[Test]
    public function refreshExpiredTokenUsesNewRefreshTokenWhenProviderReturnsOne(): void
    {
        $oauthProvider = $this->createMock(AbstractProvider::class);
        $oauthProvider
            ->expects($this->once())
            ->method('getAccessToken')
            ->with('refresh_token', ['refresh_token' => 'old-refresh'])
            ->willReturn(new AccessToken(['access_token' => 'new-access', 'refresh_token' => 'new-refresh']))
        ;

        $client = $this->createMock(OAuth2ClientInterface::class);
        $client->expects($this->once())->method('getOAuth2Provider')->willReturn($oauthProvider);

        $clientRegistry = $this->createMock(ClientRegistry::class);
        $clientRegistry->expects($this->once())->method('getClient')->willReturn($client);

        $refresher = new UserRefresher($clientRegistry, $this->createStub(LoggerInterface::class));

        $user = new User(
            'douze',
            'TestProvider',
            new AccessToken([
                'access_token' => 'old-access',
                'refresh_token' => 'old-refresh',
                'expires_in' => -1,
            ]),
            'test-session-token',
        );

        $newUser = $refresher->refresh($user);

        $this->assertSame('new-access', $newUser->getAccessToken()->getToken());
        $this->assertSame('new-refresh', $newUser->getAccessToken()->getRefreshToken());
    }

    #[Test]
    public function refreshExpiredTokenWithoutRefreshToken(): void
    {
        $clientRegistry = $this->createMock(ClientRegistry::class);
        $clientRegistry
            ->expects($this->never())
            ->method('getClient')
        ;

        $provider = new UserRefresher($clientRegistry, $this->createStub(LoggerInterface::class));

        $user = new User(
            'douze',
            'TestProvider',
            new AccessToken([
                'access_token' => 'dzadzz',
                'expires_in' => -1,
            ]),
            'test-session-token',
        );

        $this->expectException(AuthenticationExpiredException::class);
        $this->expectExceptionMessageIsOrContains('Access token expired and no refresh token available.');

        $provider->refresh($user);
    }

    #[Test]
    public function refreshExpiredTokenWhenProviderRejectsRefreshToken(): void
    {
        $oauthProvider = $this->createMock(AbstractProvider::class);
        $identityProviderException = new IdentityProviderException('invalid_grant', 400, 'invalid_grant');

        $oauthProvider
            ->expects($this->once())
            ->method('getAccessToken')
            ->with('refresh_token', ['refresh_token' => 'old-refresh'])
            ->willThrowException($identityProviderException)
        ;

        $client = $this->createMock(OAuth2ClientInterface::class);
        $client->expects($this->once())->method('getOAuth2Provider')->willReturn($oauthProvider);

        $clientRegistry = $this->createMock(ClientRegistry::class);
        $clientRegistry->expects($this->once())->method('getClient')->willReturn($client);

        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects($this->once())
            ->method('warning')
            ->with(
                'Failed to refresh OAuth2 access token: {message}',
                [
                    'message' => 'invalid_grant',
                    'provider' => 'TestProvider',
                    'exception' => $identityProviderException,
                ]
            )
        ;

        $refresher = new UserRefresher($clientRegistry, $logger);

        $user = new User(
            'douze',
            'TestProvider',
            new AccessToken([
                'access_token' => 'old-access',
                'refresh_token' => 'old-refresh',
                'expires_in' => -1,
            ]),
            'test-session-token',
        );

        $this->expectException(AuthenticationExpiredException::class);
        $this->expectExceptionMessageIsOrContains('Access token refresh failed.');

        $refresher->refresh($user);
    }

    #[Test]
    public function refreshExpiredTokenLooksUpClientWithLowercasedProviderName(): void
    {
        $invalidClientException = new InvalidOAuth2ClientException(
            'There is no OAuth2 client called "Google". Available are: google, discord'
        );

        $clientRegistry = $this->createMock(ClientRegistry::class);
        $clientRegistry
            ->expects($this->once())
            ->method('getClient')
            ->with('google')
            ->willThrowException($invalidClientException)
        ;

        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects($this->once())
            ->method('warning')
            ->with(
                'Failed to refresh OAuth2 access token: {message}',
                [
                    'message' => $invalidClientException->getMessage(),
                    'provider' => 'Google',
                    'exception' => $invalidClientException,
                ]
            )
        ;

        $refresher = new UserRefresher($clientRegistry, $logger);

        $user = new User(
            'douze',
            'Google',
            new AccessToken([
                'access_token' => 'old-access',
                'refresh_token' => 'old-refresh',
                'expires_in' => -1,
            ]),
            'test-session-token',
        );

        $this->expectException(AuthenticationExpiredException::class);
        $this->expectExceptionMessageIsOrContains('Access token refresh failed.');

        $refresher->refresh($user);
    }

    #[Test]
    public function refreshNotExpiredToken(): void
    {
        $clientRegistry = $this->createMock(ClientRegistry::class);
        $clientRegistry
            ->expects($this->never())
            ->method('getClient')
        ;

        $provider = new UserRefresher($clientRegistry, $this->createStub(LoggerInterface::class));

        $user = new User(
            'douze',
            'TestProvider',
            new AccessToken([
                'access_token' => 'dzadzz',
                'expires_in' => 1000000000000000000,
            ]),
            'test-session-token',
        );

        $freshUser = $provider->refresh($user);

        $this->assertSame($user, $freshUser);
    }
}
