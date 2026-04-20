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
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

/**
 * @internal
 */
trait AuthenticatorAuthenticateOpenedTestTrait
{
    public function testOpenedAuthenticateUser(): void
    {
        $authenticator = $this->getOpenedAuthenticator([]);

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
        $this->assertEquals($this->getAuthenticatorProviderName(), $user->getProviderName());
    }

    public function testOpenedAuthenticateTrainer(): void
    {
        $authenticator = $this->getOpenedAuthenticator(['ROLE_TRAINER']);

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
    }

    public function testOpenedAuthenticateCollector(): void
    {
        $authenticator = $this->getOpenedAuthenticator(['ROLE_COLLECTOR']);

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
    }

    public function testOpenedAuthenticateAdmin(): void
    {
        $authenticator = $this->getOpenedAuthenticator(['ROLE_ADMIN']);

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
    }

    public function testOpenedAuthenticateAdminTrainer(): void
    {
        $authenticator = $this->getOpenedAuthenticator(['ROLE_TRAINER', 'ROLE_ADMIN']);

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
    }

    /**
     * @param array<int, string> $roles
     */
    private function getOpenedAuthenticator(array $roles): OAuth2Authenticator
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
