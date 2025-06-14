<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Exception\NoLoggedUserException;
use App\Security\UserTokenService;
use App\Service\TrainerIdsService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * @internal
 */
#[CoversClass(TrainerIdsService::class)]
class TrainerIdsServiceTest extends TestCase
{
    public function testInit(): void
    {
        $userTokenService = $this->createMock(UserTokenService::class);
        $userTokenService
            ->expects($this->once())
            ->method('getLoggedUserToken')
            ->willReturn('8800088')
        ;

        $requestStack = new RequestStack();
        $request = new Request(['t' => '2100012']);
        $requestStack->push($request);

        $service = new TrainerIdsService($userTokenService, $requestStack);
        $service->init();

        $this->assertSame('8800088', $service->getLoggedTrainerId());
        $this->assertSame('2100012', $service->getRequestedTrainerId());
        $this->assertSame('2100012', $service->getTrainerId());
    }

    public function testInitWithoutRequested(): void
    {
        $userTokenService = $this->createMock(UserTokenService::class);
        $userTokenService
            ->expects($this->once())
            ->method('getLoggedUserToken')
            ->willReturn('8800088')
        ;

        $requestStack = new RequestStack();
        $request = new Request();
        $requestStack->push($request);

        $service = new TrainerIdsService($userTokenService, $requestStack);
        $service->init();

        $this->assertSame('8800088', $service->getLoggedTrainerId());
        $this->assertSame('', $service->getRequestedTrainerId());
        $this->assertSame('8800088', $service->getTrainerId());
    }

    public function testInitWithoutLogged(): void
    {
        $userTokenService = $this->createMock(UserTokenService::class);
        $userTokenService
            ->expects($this->once())
            ->method('getLoggedUserToken')
            ->willThrowException(new NoLoggedUserException())
        ;

        $requestStack = new RequestStack();
        $request = new Request(['t' => '2100012']);
        $requestStack->push($request);

        $service = new TrainerIdsService($userTokenService, $requestStack);
        $service->init();

        $this->assertNull($service->getLoggedTrainerId());
        $this->assertSame('2100012', $service->getRequestedTrainerId());
        $this->assertSame('2100012', $service->getTrainerId());
    }

    public function testInitWithoutLoggedAndRequested(): void
    {
        $userTokenService = $this->createMock(UserTokenService::class);
        $userTokenService
            ->expects($this->once())
            ->method('getLoggedUserToken')
            ->willThrowException(new NoLoggedUserException())
        ;

        $requestStack = new RequestStack();
        $request = new Request();
        $requestStack->push($request);

        $service = new TrainerIdsService($userTokenService, $requestStack);
        $service->init();

        $this->assertNull($service->getLoggedTrainerId());
        $this->assertSame('', $service->getRequestedTrainerId());
        $this->assertSame('', $service->getTrainerId());
    }
}
