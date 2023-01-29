<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security;

use App\Security\GoogleAuthenticator;
use App\Security\User;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Client\OAuth2ClientInterface;
use League\OAuth2\Client\Provider\GoogleUser;
use League\OAuth2\Client\Token\AccessToken;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

class GoogleAuthenticatorAuthenticateTest extends TestCase
{
    public function testAuthenticateUser(): void
    {
        $googleAuthenticator = $this->getGoogleAuthenticator(
            '1313131313',
            '2121212121,1313131313'
        );

        $request = $this->createMock(Request::class);

        $validationPassport = $googleAuthenticator->authenticate($request);

        $this->assertInstanceOf(SelfValidatingPassport::class, $validationPassport);

        /** @var User $user */
        $user = $validationPassport->getUser();
        $this->assertFalse($user->isAnAdmin());
        $this->assertFalse($user->isATrainer());
        $this->assertEquals('1212121212', $user->getId());
        $this->assertEquals('1212121212', $user->getUserIdentifier());
    }

    public function testAuthenticateTrainer(): void
    {
        $googleAuthenticator = $this->getGoogleAuthenticator(
            '1313131313',
            '2121212121,1313131313,1212121212'
        );

        $request = $this->createMock(Request::class);

        $validationPassport = $googleAuthenticator->authenticate($request);

        $this->assertInstanceOf(SelfValidatingPassport::class, $validationPassport);

        /** @var User $user */
        $user = $validationPassport->getUser();
        $this->assertFalse($user->isAnAdmin());
        $this->assertTrue($user->isATrainer());
        $this->assertEquals('1212121212', $user->getId());
        $this->assertEquals('1212121212', $user->getUserIdentifier());
    }

    public function testAuthenticateAdmin(): void
    {
        $googleAuthenticator = $this->getGoogleAuthenticator(
            '1313131313,1212121212',
            '2121212121,1313131313'
        );

        $request = $this->createMock(Request::class);

        $validationPassport = $googleAuthenticator->authenticate($request);

        $this->assertInstanceOf(SelfValidatingPassport::class, $validationPassport);

        /** @var User $user */
        $user = $validationPassport->getUser();
        $this->assertTrue($user->isAnAdmin());
        $this->assertFalse($user->isATrainer());
        $this->assertEquals('1212121212', $user->getId());
        $this->assertEquals('1212121212', $user->getUserIdentifier());
    }

    public function testAuthenticateAdminTrainer(): void
    {
        $googleAuthenticator = $this->getGoogleAuthenticator(
            '1313131313,1212121212',
            '2121212121,1313131313,1212121212'
        );

        $request = $this->createMock(Request::class);

        $validationPassport = $googleAuthenticator->authenticate($request);

        $this->assertInstanceOf(SelfValidatingPassport::class, $validationPassport);

        /** @var User $user */
        $user = $validationPassport->getUser();
        $this->assertTrue($user->isAnAdmin());
        $this->assertTrue($user->isATrainer());
        $this->assertEquals('1212121212', $user->getId());
        $this->assertEquals('1212121212', $user->getUserIdentifier());
    }

    private function getGoogleAuthenticator(string $listAdmin, string $listTrainer): GoogleAuthenticator
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
                'sub' => '1212121212',
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

        return new GoogleAuthenticator(
            $clientRegistry,
            $router,
            $listAdmin,
            $listTrainer
        );
    }
}
