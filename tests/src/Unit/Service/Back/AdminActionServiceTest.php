<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Back;

use App\Exception\NoLoggedUserException;
use App\Security\UserTokenService;
use App\Service\Back\AdminActionService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * @internal
 */
#[CoversClass(AdminActionService::class)]
class AdminActionServiceTest extends TestCase
{
    public function testExecute(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects($this->exactly(2))
            ->method('info')
        ;

        $response = $this->createMock(ResponseInterface::class);
        $response
            ->expects($this->exactly(2))
            ->method('getContent')
            ->willReturn('{"action": "update", "item": "something", "state": "ok", "content": "", "error": ""}')
        ;

        $client = $this->createMock(HttpClientInterface::class);
        $client
            ->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                'https://back.local/istration/action/update/something',
                [
                    'headers' => [
                        'accept' => 'application/json',
                        'Authorization' => 'Bearer dzdz-access-token-dzdz',
                    ],
                    'cafile' => '/some/where/cafile.pem',
                ],
            )
            ->willReturn($response)
        ;

        $userTokenService = $this->createMock(UserTokenService::class);
        $userTokenService
            ->expects($this->once())
            ->method('getLoggedUserToken')
            ->willReturn('dzdz-access-token-dzdz')
        ;

        $service = new AdminActionService(
            $logger,
            $client,
            'https://back.local',
            '/some/where/cafile.pem',
            $userTokenService,
        );

        $adminAction = $service->execute('update', 'something');

        $this->assertSame('update', $adminAction->action);
        $this->assertSame('something', $adminAction->item);
        $this->assertSame('ok', $adminAction->state);
        $this->assertSame('', $adminAction->content);
        $this->assertSame('', $adminAction->error);
    }

    public function testExecuteWithContentAndError(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects($this->exactly(2))
            ->method('info')
        ;

        $response = $this->createMock(ResponseInterface::class);
        $response
            ->expects($this->exactly(2))
            ->method('getContent')
            ->willReturn('{"action": "update", "item": "something", "state": "ok", "content": "foobar", "error": "oops"}')
        ;

        $client = $this->createMock(HttpClientInterface::class);
        $client
            ->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                'https://back.local/istration/action/update/something',
                [
                    'headers' => [
                        'accept' => 'application/json',
                        'Authorization' => 'Bearer dzdz-access-token-dzdz',
                    ],
                    'cafile' => '/some/where/cafile.pem',
                ],
            )
            ->willReturn($response)
        ;

        $userTokenService = $this->createMock(UserTokenService::class);
        $userTokenService
            ->expects($this->once())
            ->method('getLoggedUserToken')
            ->willReturn('dzdz-access-token-dzdz')
        ;

        $service = new AdminActionService(
            $logger,
            $client,
            'https://back.local',
            '/some/where/cafile.pem',
            $userTokenService,
        );

        $adminAction = $service->execute('update', 'something');

        $this->assertSame('update', $adminAction->action);
        $this->assertSame('something', $adminAction->item);
        $this->assertSame('ok', $adminAction->state);
        $this->assertSame('foobar', $adminAction->content);
        $this->assertSame('oops', $adminAction->error);
    }

    public function testExecuteWithoutLoggedUser(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects($this->exactly(2))
            ->method('info')
        ;

        $response = $this->createMock(ResponseInterface::class);
        $response
            ->expects($this->exactly(2))
            ->method('getContent')
            ->willReturn('{"action": "update", "item": "something", "state": "ok", "content": "", "error": ""}')
        ;

        $client = $this->createMock(HttpClientInterface::class);
        $client
            ->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                'https://back.local/istration/action/update/something',
                [
                    'headers' => [
                        'accept' => 'application/json',
                    ],
                    'cafile' => '/some/where/cafile.pem',
                ],
            )
            ->willReturn($response)
        ;

        $userTokenService = $this->createMock(UserTokenService::class);
        $userTokenService
            ->expects($this->once())
            ->method('getLoggedUserToken')
            ->willThrowException(new NoLoggedUserException('No logged user'))
        ;

        $service = new AdminActionService(
            $logger,
            $client,
            'https://back.local',
            '/some/where/cafile.pem',
            $userTokenService,
        );

        $adminAction = $service->execute('update', 'something');

        $this->assertSame('update', $adminAction->action);
        $this->assertSame('something', $adminAction->item);
        $this->assertSame('ok', $adminAction->state);
        $this->assertSame('', $adminAction->content);
        $this->assertSame('', $adminAction->error);
    }
}
