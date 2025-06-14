<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security;

use App\Security\User;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Client\OAuth2ClientInterface;
use KnpU\OAuth2ClientBundle\Security\Authenticator\OAuth2Authenticator;
use League\OAuth2\Client\Provider\GoogleUser;
use League\OAuth2\Client\Token\AccessToken;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

/**
 * @internal
 */
trait AuthenticatorAuthenticateClosedTestTrait
{
    public function testClosedAuthenticateUser(): void
    {
        $authenticator = $this->getAuthenticator(
            '1313131313',
            '2121212121,1313131313',
            '2121212121',
        );

        $request = $this->createMock(Request::class);

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
    }

    public function testClosedAuthenticateTrainer(): void
    {
        $authenticator = $this->getAuthenticator(
            '1313131313',
            '2121212121,1313131313,1212121212000000000000012',
            '2121212121,1313131313',
        );

        $request = $this->createMock(Request::class);

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

    public function testClosedAuthenticateCollector(): void
    {
        $authenticator = $this->getAuthenticator(
            '1313131313',
            '2121212121,1313131313',
            '2121212121,1212121212000000000000012',
        );

        $request = $this->createMock(Request::class);

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

    public function testClosedAuthenticateAdmin(): void
    {
        $authenticator = $this->getAuthenticator(
            '1313131313,1212121212000000000000012',
            '2121212121,1313131313',
            '2121212121',
        );

        $request = $this->createMock(Request::class);

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

    public function testClosedAuthenticateAdminTrainer(): void
    {
        $authenticator = $this->getAuthenticator(
            '1313131313,1212121212000000000000012',
            '2121212121,1313131313,1212121212000000000000012',
            '2121212121,',
        );

        $request = $this->createMock(Request::class);

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

    public function testClosedAuthenticateAdminTrainerWithEndlines(): void
    {
        $listAdmin = <<<'LIST'
            toto,

            1212121212000000000000012,

            01234567890123456789011
            LIST;
        $listTrainer = <<<'LIST'
            titi,

            1212121212000000000000012,

            0123456789012345678901,
            11655986856658439236105875191
            LIST;
        $listCollector = <<<'LIST'
            tata,
            1212121212000000000000012,
            LIST;

        $authenticator = $this->getAuthenticator($listAdmin, $listTrainer, $listCollector);

        $request = $this->createMock(Request::class);

        $validationPassport = $authenticator->authenticate($request);

        $this->assertInstanceOf(SelfValidatingPassport::class, $validationPassport);

        /** @var User $user */
        $user = $validationPassport->getUser();
        $this->assertTrue($user->isAnAdmin());
        $this->assertTrue($user->isATrainer());
        $this->assertTrue($user->isACollector());
        $this->assertEquals('1212121212000000000000012', $user->getId());
        $this->assertEquals('1212121212000000000000012', $user->getUserIdentifier());
        $this->assertEquals($this->getAuthenticatorProviderName(), $user->getProviderName());
    }

    private function getAuthenticator(string $listAdmin, string $listTrainer, string $listCollector): OAuth2Authenticator
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
            ->expects($this->once())
            ->method('fetchUserFromToken')
            ->willReturn(new GoogleUser([
                'sub' => '1212121212000000000000012',
                'name' => 'Douze',
            ]))
        ;

        $clientRegistry = $this->createMock(ClientRegistry::class);
        $clientRegistry
            ->expects($this->once())
            ->method('getClient')
            ->willReturn($oauth2Client)
        ;

        $router = $this->createMock(RouterInterface::class);

        /** @var OAuth2Authenticator */
        return new ($this->getAuthenticatorClassName())(
            $clientRegistry,
            $router,
            $listAdmin,
            $listTrainer,
            $listCollector,
            true,
        );
    }
}
