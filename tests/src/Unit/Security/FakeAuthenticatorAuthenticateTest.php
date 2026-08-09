<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security;

use App\Security\FakeAuthenticator;
use App\Security\User;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
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
    #[Test]
    public function authenticateUser(): void
    {
        $fakeAuthenticator = $this->getFakeAuthenticator();

        $request = Request::create('local.dev', 'GET', ['t' => 'uninvited']);

        $validationPassport = $fakeAuthenticator->authenticate($request);

        $this->assertInstanceOf(SelfValidatingPassport::class, $validationPassport);

        /** @var User $user */
        $user = $validationPassport->getUser();
        $this->assertFalse($user->isAnAdmin());
        $this->assertFalse($user->isATrainer());
        $this->assertFalse($user->isACollector());
        $this->assertEquals('uninvited', $user->getId());
        $this->assertEquals('uninvited', $user->getUserIdentifier());
    }

    #[Test]
    public function authenticateTrainer(): void
    {
        $fakeAuthenticator = $this->getFakeAuthenticator();

        $request = Request::create('local.dev', 'GET', ['t' => 'trainer']);

        $validationPassport = $fakeAuthenticator->authenticate($request);

        $this->assertInstanceOf(SelfValidatingPassport::class, $validationPassport);

        /** @var User $user */
        $user = $validationPassport->getUser();
        $this->assertFalse($user->isAnAdmin());
        $this->assertTrue($user->isATrainer());
        $this->assertFalse($user->isACollector());
        $this->assertEquals('trainer', $user->getId());
        $this->assertEquals('trainer', $user->getUserIdentifier());
    }

    #[Test]
    public function authenticateCollector(): void
    {
        $fakeAuthenticator = $this->getFakeAuthenticator();

        $request = Request::create('local.dev', 'GET', ['t' => 'collector']);

        $validationPassport = $fakeAuthenticator->authenticate($request);

        $this->assertInstanceOf(SelfValidatingPassport::class, $validationPassport);

        /** @var User $user */
        $user = $validationPassport->getUser();
        $this->assertFalse($user->isAnAdmin());
        $this->assertTrue($user->isATrainer());
        $this->assertTrue($user->isACollector());
        $this->assertEquals('collector', $user->getId());
        $this->assertEquals('collector', $user->getUserIdentifier());
    }

    #[Test]
    public function authenticateAdmin(): void
    {
        $fakeAuthenticator = $this->getFakeAuthenticator();

        $request = Request::create('local.dev', 'GET', ['t' => 'admin']);

        $validationPassport = $fakeAuthenticator->authenticate($request);

        $this->assertInstanceOf(SelfValidatingPassport::class, $validationPassport);

        /** @var User $user */
        $user = $validationPassport->getUser();
        $this->assertTrue($user->isAnAdmin());
        $this->assertTrue($user->isATrainer());
        $this->assertTrue($user->isACollector());
        $this->assertEquals('admin', $user->getId());
        $this->assertEquals('admin', $user->getUserIdentifier());
    }

    #[Test]
    public function authenticateTokenNeverExpires(): void
    {
        $fakeAuthenticator = $this->getFakeAuthenticator();

        $request = Request::create('local.dev', 'GET', ['t' => 'sometoken']);

        $validationPassport = $fakeAuthenticator->authenticate($request);

        /** @var User $user */
        $user = $validationPassport->getUser();
        $this->assertFalse($user->getAccessToken()->hasExpired());
    }

    private function getFakeAuthenticator(): FakeAuthenticator
    {
        return new FakeAuthenticator(
            $this->createStub(RouterInterface::class),
        );
    }
}
