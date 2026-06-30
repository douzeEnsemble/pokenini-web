<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security;

use App\Security\User;
use App\Security\UserRefresher;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Client\OAuth2ClientInterface;
use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Token\AccessToken;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Exception\AuthenticationExpiredException;

/**
 * @internal
 */
#[CoversClass(UserRefresher::class)]
final class UserRefresherTest extends TestCase
{
    public function testRefreshExpiredTokenPreservesOldRefreshTokenWhenProviderOmitsIt(): void
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
            ->with('TestProvider')
            ->willReturn($client)
        ;

        $refresher = new UserRefresher($clientRegistry);

        $user = new User(
            'douze',
            'TestProvider',
            new AccessToken([
                'access_token' => 'dzadzz',
                'refresh_token' => 'ipoadnaz',
                'expires_in' => -1,
            ])
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
    }

    public function testRefreshExpiredTokenUsesNewRefreshTokenWhenProviderReturnsOne(): void
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

        $refresher = new UserRefresher($clientRegistry);

        $user = new User(
            'douze',
            'TestProvider',
            new AccessToken([
                'access_token' => 'old-access',
                'refresh_token' => 'old-refresh',
                'expires_in' => -1,
            ])
        );

        $newUser = $refresher->refresh($user);

        $this->assertSame('new-access', $newUser->getAccessToken()->getToken());
        $this->assertSame('new-refresh', $newUser->getAccessToken()->getRefreshToken());
    }

    public function testRefreshExpiredTokenWithoutRefreshToken(): void
    {
        $clientRegistry = $this->createMock(ClientRegistry::class);
        $clientRegistry
            ->expects($this->never())
            ->method('getClient')
        ;

        $provider = new UserRefresher($clientRegistry);

        $user = new User(
            'douze',
            'TestProvider',
            new AccessToken([
                'access_token' => 'dzadzz',
                'expires_in' => -1,
            ])
        );

        $this->expectException(AuthenticationExpiredException::class);
        $this->expectExceptionMessageIsOrContains('Access token expired and no refresh token available.');

        $provider->refresh($user);
    }

    public function testRefreshNotExpiredToken(): void
    {
        $clientRegistry = $this->createMock(ClientRegistry::class);
        $clientRegistry
            ->expects($this->never())
            ->method('getClient')
        ;

        $provider = new UserRefresher($clientRegistry);

        $user = new User(
            'douze',
            'TestProvider',
            new AccessToken([
                'access_token' => 'dzadzz',
                'expires_in' => 1000000000000000000,
            ])
        );

        $freshUser = $provider->refresh($user);

        $this->assertSame($user, $freshUser);
    }
}
