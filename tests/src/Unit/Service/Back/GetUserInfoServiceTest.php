<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Back;

use App\Exception\NoLoggedUserException;
use App\Security\User;
use App\Security\UserTokenService;
use App\Service\Back\GetUserInfoService;
use League\OAuth2\Client\Token\AccessToken;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * @internal
 */
#[CoversClass(GetUserInfoService::class)]
class GetUserInfoServiceTest extends TestCase
{
    public const ENDPOINT = 'user-info';
    public const RESPONSE_CONTENT = '/var/www/html/tests/resources/unit/service/back/user-info.json';

    public function testGet(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects($this->exactly(2))
            ->method('info')
        ;

        $client = $this->createMock(HttpClientInterface::class);

        $json = (string) file_get_contents('/var/www/html/tests/resources/unit/service/back/user-info.json');

        $response = $this->createMock(ResponseInterface::class);
        $response
            ->expects($this->exactly(2))
            ->method('getContent')
            ->willReturn($json)
        ;

        $client
            ->expects($this->once())
            ->method('request')
            ->with(
                'GET',
                'https://api.domain/user-info',
                [
                    'headers' => [
                        'accept' => 'application/json',
                        'Authorization' => 'Bearer abcde-access-token-abcde',
                        'X-Provider' => 'TestProvider',
                    ],
                    'cafile' => './resources/certificates/cacert.pem',
                ],
            )
            ->willReturn($response)
        ;

        $user = new User(
            '12',
            'TestProvider',
            new AccessToken(['access_token' => 'abcde-access-token-abcde']),
        );

        $userTokenService = $this->createMock(UserTokenService::class);
        $userTokenService
            ->expects($this->once())
            ->method('getLoggedUser')
            ->willReturn($user)
        ;

        $service = new GetUserInfoService(
            $logger,
            $client,
            'https://api.domain',
            './resources/certificates/cacert.pem',
            $userTokenService,
        );

        $accessToken = new AccessToken(['access_token' => 'abcde-access-token-abcde']);

        $userInfo = $service->get($accessToken, 'FakeProvider');

        $this->assertSame('68464686dazazda6876a3z8d7az0', $userInfo->identifier);
        $this->assertSame(['ROLE_TRAINER', 'ROLE_COLLECTOR'], $userInfo->roles);
    }

    public function testGetWithoutLoggedUser(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects($this->exactly(2))
            ->method('info')
        ;

        $client = $this->createMock(HttpClientInterface::class);

        $json = (string) file_get_contents('/var/www/html/tests/resources/unit/service/back/user-info.json');

        $response = $this->createMock(ResponseInterface::class);
        $response
            ->expects($this->exactly(2))
            ->method('getContent')
            ->willReturn($json)
        ;

        $client
            ->expects($this->once())
            ->method('request')
            ->with(
                'GET',
                'https://api.domain/user-info',
                [
                    'headers' => [
                        'accept' => 'application/json',
                        'Authorization' => 'Bearer fghjklm-access-token-fghjklm',
                        'X-Provider' => 'FakeProvider',
                    ],
                    'cafile' => './resources/certificates/cacert.pem',
                ],
            )
            ->willReturn($response)
        ;

        $userTokenService = $this->createMock(UserTokenService::class);
        $userTokenService
            ->expects($this->once())
            ->method('getLoggedUser')
            ->willThrowException(new NoLoggedUserException())
        ;

        $service = new GetUserInfoService(
            $logger,
            $client,
            'https://api.domain',
            './resources/certificates/cacert.pem',
            $userTokenService,
        );

        $accessToken = new AccessToken(['access_token' => 'fghjklm-access-token-fghjklm']);

        $userInfo = $service->get($accessToken, 'FakeProvider');

        $this->assertSame('68464686dazazda6876a3z8d7az0', $userInfo->identifier);
        $this->assertSame(['ROLE_TRAINER', 'ROLE_COLLECTOR'], $userInfo->roles);
    }
}
