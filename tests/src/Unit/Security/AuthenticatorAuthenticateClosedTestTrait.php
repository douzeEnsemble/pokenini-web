<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security;

use App\DTO\UserInfo;
use App\Security\User;
use App\Service\Back\GetUserInfoService;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Client\OAuth2ClientInterface;
use KnpU\OAuth2ClientBundle\Security\Authenticator\OAuth2Authenticator;
use League\OAuth2\Client\Token\AccessToken;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

/**
 * @internal
 */
trait AuthenticatorAuthenticateClosedTestTrait
{
    #[Test]
    public function closedAuthenticateUser(): void
    {
        $authenticator = $this->getClosedAuthenticator([]);

        $request = $this->createStub(Request::class);

        $validationPassport = $authenticator->authenticate($request);

        $this->assertInstanceOf(SelfValidatingPassport::class, $validationPassport);

        /** @var User $user */
        $user = $validationPassport->getUser();
        $this->assertFalse($user->isAnAdmin());
        $this->assertFalse($user->isATrainer());
        $this->assertFalse($user->isACollector());
        $this->assertEquals('1212121212000000000000012', $user->getId());
        $this->assertEquals('1212121212000000000000012', $user->getUserIdentifier());
        $this->assertEquals('1212121212000000000000012', $user->getUserIdentifier());
        $this->assertEquals($this->getAuthenticatorProviderName(), $user->getProviderName());
        $this->assertEquals('session-jwt-from-back', $user->getSessionToken());
    }

    #[Test]
    public function closedAuthenticateTrainer(): void
    {
        $authenticator = $this->getClosedAuthenticator(['ROLE_TRAINER']);

        $request = $this->createStub(Request::class);

        $validationPassport = $authenticator->authenticate($request);

        $this->assertInstanceOf(SelfValidatingPassport::class, $validationPassport);

        /** @var User $user */
        $user = $validationPassport->getUser();
        $this->assertFalse($user->isAnAdmin());
        $this->assertTrue($user->isATrainer());
        $this->assertFalse($user->isACollector());
        $this->assertEquals('1212121212000000000000012', $user->getId());
        $this->assertEquals('1212121212000000000000012', $user->getUserIdentifier());
        $this->assertEquals($this->getAuthenticatorProviderName(), $user->getProviderName());
        $this->assertEquals('session-jwt-from-back', $user->getSessionToken());
    }

    #[Test]
    public function closedAuthenticateCollector(): void
    {
        $authenticator = $this->getClosedAuthenticator(['ROLE_COLLECTOR']);

        $request = $this->createStub(Request::class);

        $validationPassport = $authenticator->authenticate($request);

        $this->assertInstanceOf(SelfValidatingPassport::class, $validationPassport);

        /** @var User $user */
        $user = $validationPassport->getUser();
        $this->assertFalse($user->isAnAdmin());
        $this->assertFalse($user->isATrainer());
        $this->assertTrue($user->isACollector());
        $this->assertEquals('1212121212000000000000012', $user->getId());
        $this->assertEquals('1212121212000000000000012', $user->getUserIdentifier());
        $this->assertEquals($this->getAuthenticatorProviderName(), $user->getProviderName());
        $this->assertEquals('session-jwt-from-back', $user->getSessionToken());
    }

    #[Test]
    public function closedAuthenticateAdmin(): void
    {
        $authenticator = $this->getClosedAuthenticator(['ROLE_ADMIN']);

        $request = $this->createStub(Request::class);

        $validationPassport = $authenticator->authenticate($request);

        $this->assertInstanceOf(SelfValidatingPassport::class, $validationPassport);

        /** @var User $user */
        $user = $validationPassport->getUser();
        $this->assertTrue($user->isAnAdmin());
        $this->assertFalse($user->isATrainer());
        $this->assertFalse($user->isACollector());
        $this->assertEquals('1212121212000000000000012', $user->getId());
        $this->assertEquals('1212121212000000000000012', $user->getUserIdentifier());
        $this->assertEquals($this->getAuthenticatorProviderName(), $user->getProviderName());
        $this->assertEquals('session-jwt-from-back', $user->getSessionToken());
    }

    #[Test]
    public function closedAuthenticateAdminTrainer(): void
    {
        $authenticator = $this->getClosedAuthenticator(['ROLE_TRAINER', 'ROLE_ADMIN']);

        $request = $this->createStub(Request::class);

        $validationPassport = $authenticator->authenticate($request);

        $this->assertInstanceOf(SelfValidatingPassport::class, $validationPassport);

        /** @var User $user */
        $user = $validationPassport->getUser();
        $this->assertTrue($user->isAnAdmin());
        $this->assertTrue($user->isATrainer());
        $this->assertFalse($user->isACollector());
        $this->assertEquals('1212121212000000000000012', $user->getId());
        $this->assertEquals('1212121212000000000000012', $user->getUserIdentifier());
        $this->assertEquals($this->getAuthenticatorProviderName(), $user->getProviderName());
        $this->assertEquals('session-jwt-from-back', $user->getSessionToken());
    }

    /**
     * @param array<int, string> $roles
     */
    private function getClosedAuthenticator(array $roles): OAuth2Authenticator
    {
        $oauth2Client = $this->createMock(OAuth2ClientInterface::class);
        $oauth2Client
            ->expects($this->once())
            ->method('getAccessToken')
            ->willReturn(new AccessToken([
                'access_token' => '1douze2',
            ]))
        ;
        $oauth2Client
            ->expects($this->never())
            ->method('fetchUserFromToken')
        ;

        $clientRegistry = $this->createMock(ClientRegistry::class);
        $clientRegistry
            ->expects($this->once())
            ->method('getClient')
            ->willReturn($oauth2Client)
        ;

        $router = $this->createMock(RouterInterface::class);
        $router
            ->expects($this->never())
            ->method('generate')
        ;

        $getUserInfoService = $this->createMock(GetUserInfoService::class);
        $getUserInfoService
            ->expects($this->once())
            ->method('get')
            ->willReturn(
                new UserInfo(
                    '1212121212000000000000012',
                    'mock',
                    'trainer',
                    $roles,
                    'session-jwt-from-back',
                )
            )
        ;

        return $this->getAuthenticatorInstance(
            $clientRegistry,
            $router,
            $getUserInfoService,
        );
    }
}
