<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security;

use App\DTO\UserInfo;
use App\Security\FakeAuthenticator;
use App\Security\User;
use App\Service\Back\GetUserInfoService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

/**
 * @internal
 */
#[CoversClass(FakeAuthenticator::class)]
final class FakeAuthenticatorAuthenticateTest extends TestCase
{
    public function testAuthenticateUser(): void
    {
        $fakeAuthenticator = $this->getFakeAuthenticator([]);

        $request = Request::create('local.dev', 'GET', ['t' => '1212121212000000000000012']);

        $validationPassport = $fakeAuthenticator->authenticate($request);

        $this->assertInstanceOf(SelfValidatingPassport::class, $validationPassport);

        /** @var User $user */
        $user = $validationPassport->getUser();
        $this->assertFalse($user->isAnAdmin());
        $this->assertFalse($user->isATrainer());
        $this->assertFalse($user->isACollector());
        $this->assertEquals('1212121212000000000000012', $user->getId());
        $this->assertEquals('1212121212000000000000012', $user->getUserIdentifier());
    }

    public function testAuthenticateTrainer(): void
    {
        $fakeAuthenticator = $this->getFakeAuthenticator(['ROLE_TRAINER']);

        $request = Request::create('local.dev', 'GET', ['t' => '1212121212000000000000012']);

        $validationPassport = $fakeAuthenticator->authenticate($request);

        $this->assertInstanceOf(SelfValidatingPassport::class, $validationPassport);

        /** @var User $user */
        $user = $validationPassport->getUser();
        $this->assertFalse($user->isAnAdmin());
        $this->assertTrue($user->isATrainer());
        $this->assertFalse($user->isACollector());
        $this->assertEquals('1212121212000000000000012', $user->getId());
        $this->assertEquals('1212121212000000000000012', $user->getUserIdentifier());
    }

    public function testAuthenticateCollector(): void
    {
        $fakeAuthenticator = $this->getFakeAuthenticator(['ROLE_COLLECTOR']);

        $request = Request::create('local.dev', 'GET', ['t' => '1212121212000000000000012']);

        $validationPassport = $fakeAuthenticator->authenticate($request);

        $this->assertInstanceOf(SelfValidatingPassport::class, $validationPassport);

        /** @var User $user */
        $user = $validationPassport->getUser();
        $this->assertFalse($user->isAnAdmin());
        $this->assertFalse($user->isATrainer());
        $this->assertTrue($user->isACollector());
        $this->assertEquals('1212121212000000000000012', $user->getId());
        $this->assertEquals('1212121212000000000000012', $user->getUserIdentifier());
    }

    public function testAuthenticateAdmin(): void
    {
        $fakeAuthenticator = $this->getFakeAuthenticator(['ROLE_ADMIN']);

        $request = Request::create('local.dev', 'GET', ['t' => '1212121212000000000000012']);

        $validationPassport = $fakeAuthenticator->authenticate($request);

        $this->assertInstanceOf(SelfValidatingPassport::class, $validationPassport);

        /** @var User $user */
        $user = $validationPassport->getUser();
        $this->assertTrue($user->isAnAdmin());
        $this->assertFalse($user->isATrainer());
        $this->assertFalse($user->isACollector());
        $this->assertEquals('1212121212000000000000012', $user->getId());
        $this->assertEquals('1212121212000000000000012', $user->getUserIdentifier());
    }

    public function testAuthenticateAdminTrainer(): void
    {
        $fakeAuthenticator = $this->getFakeAuthenticator(['ROLE_TRAINER', 'ROLE_ADMIN']);

        $request = Request::create('local.dev', 'GET', ['t' => '1212121212000000000000012']);

        $validationPassport = $fakeAuthenticator->authenticate($request);

        $this->assertInstanceOf(SelfValidatingPassport::class, $validationPassport);

        /** @var User $user */
        $user = $validationPassport->getUser();
        $this->assertTrue($user->isAnAdmin());
        $this->assertTrue($user->isATrainer());
        $this->assertFalse($user->isACollector());
        $this->assertEquals('1212121212000000000000012', $user->getId());
        $this->assertEquals('1212121212000000000000012', $user->getUserIdentifier());
    }

    /**
     * @param array<int, string> $roles
     */
    private function getFakeAuthenticator(array $roles): FakeAuthenticator
    {
        $router = $this->createMock(RouterInterface::class);

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

        return new FakeAuthenticator(
            $router,
            $getUserInfoService,
        );
    }
}
