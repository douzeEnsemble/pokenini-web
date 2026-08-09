<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Back;

use App\Exception\NoLoggedUserException;
use App\Security\User;
use App\Security\UserTokenServiceInterface;
use App\Service\Back\AdminActionService;
use App\Tests\Utils\WithConsecutive;
use League\OAuth2\Client\Token\AccessToken;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * @internal
 */
#[CoversClass(AdminActionService::class)]
final class AdminActionServiceTest extends TestCase
{
    #[Test]
    public function execute(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects($this->exactly(2))
            ->method('info')
            ->with(...WithConsecutive::create(...[
                [
                    'Requesting POST /istration/action/update/something',
                    [
                        'headers' => [
                            'accept' => 'application/json',
                            'Authorization' => '[REDACTED]',
                            'X-Provider' => 'testprovider',
                        ],
                        'cafile' => '/some/where/cafile.pem',
                    ],
                ],
                [
                    'Response status code: 200',
                    [
                        'response' => '{"action": "update", "item": "something", "state": "ok", "content": "", "error": ""}',
                    ],
                ],
            ]))
        ;

        $response = $this->createMock(ResponseInterface::class);
        $response
            ->expects($this->exactly(2))
            ->method('getContent')
            ->willReturn('{"action": "update", "item": "something", "state": "ok", "content": "", "error": ""}')
        ;
        $response
            ->expects($this->once())
            ->method('getStatusCode')
            ->willReturn(200)
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
                        'Authorization' => 'Bearer test-session-token',
                        'X-Provider' => 'testprovider',
                    ],
                    'cafile' => '/some/where/cafile.pem',
                ],
            )
            ->willReturn($response)
        ;

        $user = new User(
            '12',
            'TestProvider',
            new AccessToken(['access_token' => 'dzdz-access-token-dzdz']),
            'test-session-token',
        );

        $userTokenService = $this->createMock(UserTokenServiceInterface::class);
        $userTokenService
            ->expects($this->once())
            ->method('getLoggedUser')
            ->willReturn($user)
        ;

        $serializer = $this->createStub(SerializerInterface::class);

        $service = new AdminActionService(
            $logger,
            $client,
            'https://back.local',
            '/some/where/cafile.pem',
            $userTokenService,
            $serializer,
        );

        $adminAction = $service->execute('update', 'something', 'POST');

        $this->assertSame('update', $adminAction->action);
        $this->assertSame('something', $adminAction->item);
        $this->assertSame('ok', $adminAction->state);
        $this->assertSame('', $adminAction->content);
        $this->assertSame('', $adminAction->error);
    }

    #[Test]
    public function executeWithContentAndError(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects($this->exactly(2))
            ->method('info')
            ->with(...WithConsecutive::create(...[
                [
                    'Requesting POST /istration/action/update/something',
                    [
                        'headers' => [
                            'accept' => 'application/json',
                            'Authorization' => '[REDACTED]',
                            'X-Provider' => 'testprovider',
                        ],
                        'cafile' => '/some/where/cafile.pem',
                    ],
                ],
                [
                    'Response status code: 200',
                    [
                        'response' => '{"action": "update", "item": "something", "state": "ok", "content": "foobar", "error": "oops"}',
                    ],
                ],
            ]))
        ;

        $response = $this->createMock(ResponseInterface::class);
        $response
            ->expects($this->exactly(2))
            ->method('getContent')
            ->willReturn('{"action": "update", "item": "something", "state": "ok", "content": "foobar", "error": "oops"}')
        ;
        $response
            ->expects($this->once())
            ->method('getStatusCode')
            ->willReturn(200)
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
                        'Authorization' => 'Bearer test-session-token',
                        'X-Provider' => 'testprovider',
                    ],
                    'cafile' => '/some/where/cafile.pem',
                ],
            )
            ->willReturn($response)
        ;

        $user = new User(
            '12',
            'TestProvider',
            new AccessToken(['access_token' => 'dzdz-access-token-dzdz']),
            'test-session-token',
        );

        $userTokenService = $this->createMock(UserTokenServiceInterface::class);
        $userTokenService
            ->expects($this->once())
            ->method('getLoggedUser')
            ->willReturn($user)
        ;

        $serializer = $this->createStub(SerializerInterface::class);

        $service = new AdminActionService(
            $logger,
            $client,
            'https://back.local',
            '/some/where/cafile.pem',
            $userTokenService,
            $serializer,
        );

        $adminAction = $service->execute('update', 'something', 'POST');

        $this->assertSame('update', $adminAction->action);
        $this->assertSame('something', $adminAction->item);
        $this->assertSame('ok', $adminAction->state);
        $this->assertSame('foobar', $adminAction->content);
        $this->assertSame('oops', $adminAction->error);
    }

    #[Test]
    public function executeWithoutLoggedUser(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects($this->exactly(2))
            ->method('info')
            ->with(...WithConsecutive::create(...[
                [
                    'Requesting DELETE /istration/action/update/something',
                    [
                        'headers' => [
                            'accept' => 'application/json',
                        ],
                        'cafile' => '/some/where/cafile.pem',
                    ],
                ],
                [
                    'Response status code: 200',
                    [
                        'response' => '{"action": "update", "item": "something", "state": "ok", "content": "", "error": ""}',
                    ],
                ],
            ]))
        ;

        $response = $this->createMock(ResponseInterface::class);
        $response
            ->expects($this->exactly(2))
            ->method('getContent')
            ->willReturn('{"action": "update", "item": "something", "state": "ok", "content": "", "error": ""}')
        ;
        $response
            ->expects($this->once())
            ->method('getStatusCode')
            ->willReturn(200)
        ;

        $client = $this->createMock(HttpClientInterface::class);
        $client
            ->expects($this->once())
            ->method('request')
            ->with(
                'DELETE',
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

        $userTokenService = $this->createMock(UserTokenServiceInterface::class);
        $userTokenService
            ->expects($this->once())
            ->method('getLoggedUser')
            ->willThrowException(new NoLoggedUserException('No logged user'))
        ;

        $serializer = $this->createStub(SerializerInterface::class);

        $service = new AdminActionService(
            $logger,
            $client,
            'https://back.local',
            '/some/where/cafile.pem',
            $userTokenService,
            $serializer,
        );

        $adminAction = $service->execute('update', 'something', 'DELETE');

        $this->assertSame('update', $adminAction->action);
        $this->assertSame('something', $adminAction->item);
        $this->assertSame('ok', $adminAction->state);
        $this->assertSame('', $adminAction->content);
        $this->assertSame('', $adminAction->error);
    }
}
