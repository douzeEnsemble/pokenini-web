<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security;

use App\DTO\UserInfo;
use App\Security\E2eAuthenticator;
use App\Security\User;
use App\Service\Back\GetUserInfoService;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use League\OAuth2\Client\Token\AccessToken;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

/**
 * @internal
 */
#[CoversClass(E2eAuthenticator::class)]
final class E2eAuthenticatorTest extends TestCase
{
    #[Test]
    public function supports(): void
    {
        $authenticator = $this->getAuthenticator($this->createStub(GetUserInfoService::class));

        $request = new Request();
        $request->attributes->set('_route', 'app_connect_e2e_check');
        $this->assertTrue($authenticator->supports($request));

        $request->attributes->set('_route', 'app_connect_google_check');
        $this->assertFalse($authenticator->supports($request));
    }

    #[Test]
    public function authenticate(): void
    {
        $getUserInfoService = $this->createMock(GetUserInfoService::class);
        $getUserInfoService
            ->expects($this->once())
            ->method('get')
            ->with(
                $this->callback(fn (AccessToken $accessToken) => 'the-e2e-secret' === $accessToken->getToken()),
                'e2e',
            )
            ->willReturn(new UserInfo('e2e-test-trainer', 'e2e', 'trainer', ['ROLE_USER', 'ROLE_TRAINER'], 'session-jwt-from-back'))
        ;

        $request = new Request();
        $request->headers->set('X-E2E-Login', 'the-e2e-secret');

        $passport = $this->getAuthenticator($getUserInfoService)->authenticate($request);

        $this->assertInstanceOf(SelfValidatingPassport::class, $passport);
        $this->assertSame('e2e', $passport->getBadge(UserBadge::class)?->getUserIdentifier());

        /** @var User $user */
        $user = $passport->getUser();
        $this->assertSame('e2e-test-trainer', $user->getId());
        $this->assertSame('e2e', $user->getProviderName());
        $this->assertSame('session-jwt-from-back', $user->getSessionToken());
        $this->assertTrue($user->isATrainer());
        $this->assertFalse($user->isACollector());
        $this->assertFalse($user->isAnAdmin());
    }

    #[Test]
    public function authenticateWithoutSecret(): void
    {
        $getUserInfoService = $this->createMock(GetUserInfoService::class);
        $getUserInfoService->expects($this->never())->method('get');

        $this->expectException(BadCredentialsException::class);
        $this->expectExceptionMessage('Missing E2E login.');

        $this->getAuthenticator($getUserInfoService)->authenticate(new Request());
    }

    #[Test]
    public function authenticateWithEmptySecret(): void
    {
        $getUserInfoService = $this->createMock(GetUserInfoService::class);
        $getUserInfoService->expects($this->never())->method('get');

        $request = new Request();
        $request->headers->set('X-E2E-Login', '');

        $this->expectException(BadCredentialsException::class);
        $this->expectExceptionMessage('Missing E2E login.');

        $this->getAuthenticator($getUserInfoService)->authenticate($request);
    }

    private function getAuthenticator(GetUserInfoService $getUserInfoService): E2eAuthenticator
    {
        return new E2eAuthenticator(
            $this->createStub(ClientRegistry::class),
            $this->createStub(RouterInterface::class),
            $getUserInfoService,
        );
    }
}
