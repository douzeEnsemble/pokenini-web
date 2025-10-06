<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Security\User;
use App\Service\Back\GetDexService;
use App\Service\GetDexByRoleService;
use League\OAuth2\Client\Token\AccessToken;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * @internal
 */
#[CoversClass(GetDexByRoleService::class)]
class GetDexByRoleServiceTest extends TestCase
{
    public function testGetUserDexAsTrainer(): void
    {
        $getDexService = $this->createMock(GetDexService::class);
        $getDexService
            ->expects($this->once())
            ->method('getWithPremium')
            ->with()
            ->willReturn([
                ['un'],
                ['dos'],
                ['tres'],
            ])
        ;
        $getDexService
            ->expects($this->never())
            ->method('get')
        ;
        $getDexService
            ->expects($this->never())
            ->method('getWithUnreleased')
        ;
        $getDexService
            ->expects($this->never())
            ->method('getWithUnreleasedAndPremium')
        ;

        $user = new User('1234567890', 'TestProvider', new AccessToken(['access_token' => sha1('1234567890')]));
        $user->addTrainerRole();

        $security = $this->createMock(Security::class);
        $security
            ->expects($this->once())
            ->method('getUser')
            ->willReturn($user)
        ;

        $service = new GetDexByRoleService(
            $getDexService,
            $security
        );

        $dex = $service->getUserDex();

        $this->assertSame(
            [
                ['un'],
                ['dos'],
                ['tres'],
            ],
            $dex
        );
    }

    public function testGetUserDexAsCollector(): void
    {
        $getDexService = $this->createMock(GetDexService::class);
        $getDexService
            ->expects($this->once())
            ->method('getWithPremium')
            ->with()
            ->willReturn([
                ['un'],
                ['dos'],
                ['tres'],
            ])
        ;
        $getDexService
            ->expects($this->never())
            ->method('get')
        ;
        $getDexService
            ->expects($this->never())
            ->method('getWithUnreleased')
        ;
        $getDexService
            ->expects($this->never())
            ->method('getWithUnreleasedAndPremium')
        ;

        $user = new User('1234567890', 'TestProvider', new AccessToken(['access_token' => sha1('1234567890')]));
        $user->addTrainerRole();
        $user->addCollectorRole();

        $security = $this->createMock(Security::class);
        $security
            ->expects($this->once())
            ->method('getUser')
            ->willReturn($user)
        ;

        $service = new GetDexByRoleService(
            $getDexService,
            $security
        );

        $dex = $service->getUserDex();

        $this->assertSame(
            [
                ['un'],
                ['dos'],
                ['tres'],
            ],
            $dex
        );
    }

    public function testGetUserDexAsAdmin(): void
    {
        $getDexService = $this->createMock(GetDexService::class);
        $getDexService
            ->expects($this->once())
            ->method('getWithUnreleasedAndPremium')
            ->with()
            ->willReturn([
                ['un'],
                ['dos'],
                ['tres'],
            ])
        ;
        $getDexService
            ->expects($this->never())
            ->method('get')
        ;
        $getDexService
            ->expects($this->never())
            ->method('getWithPremium')
        ;
        $getDexService
            ->expects($this->never())
            ->method('getWithUnreleased')
        ;

        $user = new User('1234567890', 'TestProvider', new AccessToken(['access_token' => sha1('1234567890')]));
        $user->addTrainerRole();
        $user->addAdminRole();

        $security = $this->createMock(Security::class);
        $security
            ->expects($this->once())
            ->method('getUser')
            ->willReturn($user)
        ;

        $service = new GetDexByRoleService(
            $getDexService,
            $security
        );

        $dex = $service->getUserDex();

        $this->assertSame(
            [
                ['un'],
                ['dos'],
                ['tres'],
            ],
            $dex
        );
    }

    public function testGetUserDexAsNull(): void
    {
        $getDexService = $this->createMock(GetDexService::class);
        $getDexService
            ->expects($this->never())
            ->method('getWithUnreleasedAndPremium')
        ;
        $getDexService
            ->expects($this->never())
            ->method('get')
        ;
        $getDexService
            ->expects($this->never())
            ->method('getWithPremium')
        ;
        $getDexService
            ->expects($this->never())
            ->method('getWithUnreleased')
        ;

        $security = $this->createMock(Security::class);
        $security
            ->expects($this->once())
            ->method('getUser')
            ->willReturn(null)
        ;

        $service = new GetDexByRoleService(
            $getDexService,
            $security
        );

        $dex = $service->getUserDex();

        $this->assertEmpty($dex);
    }
}
