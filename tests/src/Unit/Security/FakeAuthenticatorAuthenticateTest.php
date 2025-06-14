<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security;

use App\Security\FakeAuthenticator;
use App\Security\User;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

/**
 * @internal
 */
#[CoversClass(FakeAuthenticator::class)]
class FakeAuthenticatorAuthenticateTest extends TestCase
{
    public function testAuthenticateUser(): void
    {
        $fakeAuthenticator = $this->getFakeAuthenticator(
            '1313131313',
            '2121212121,1313131313',
            '2121212121',
        );

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
        $fakeAuthenticator = $this->getFakeAuthenticator(
            '1313131313',
            '2121212121,1313131313,1212121212000000000000012',
            '2121212121,1313131313',
        );

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
        $fakeAuthenticator = $this->getFakeAuthenticator(
            '1313131313',
            '2121212121,1313131313,1212121212000000000000012',
            '2121212121',
        );

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

    public function testAuthenticateAdmin(): void
    {
        $fakeAuthenticator = $this->getFakeAuthenticator(
            '1313131313,1212121212000000000000012',
            '2121212121,1313131313',
            '2121212121',
        );

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
        $fakeAuthenticator = $this->getFakeAuthenticator(
            '1313131313,1212121212000000000000012',
            '2121212121,1313131313,1212121212000000000000012',
            '2121212121,',
        );

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

    public function testAuthenticateAdminTrainerWithEndlines(): void
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

        $fakeAuthenticator = $this->getFakeAuthenticator($listAdmin, $listTrainer, $listCollector);

        $request = Request::create('local.dev', 'GET', ['t' => '1212121212000000000000012']);

        $validationPassport = $fakeAuthenticator->authenticate($request);

        $this->assertInstanceOf(SelfValidatingPassport::class, $validationPassport);

        /** @var User $user */
        $user = $validationPassport->getUser();
        $this->assertTrue($user->isAnAdmin());
        $this->assertTrue($user->isATrainer());
        $this->assertTrue($user->isACollector());
        $this->assertEquals('1212121212000000000000012', $user->getId());
        $this->assertEquals('1212121212000000000000012', $user->getUserIdentifier());
    }

    private function getFakeAuthenticator(string $listAdmin, string $listTrainer, string $listCollector): FakeAuthenticator
    {
        $router = $this->createMock(RouterInterface::class);

        return new FakeAuthenticator(
            $router,
            $listAdmin,
            $listTrainer,
            $listCollector,
            true,
        );
    }
}
