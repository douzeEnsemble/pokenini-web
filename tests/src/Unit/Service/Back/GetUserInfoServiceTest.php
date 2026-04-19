<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Back;

use App\DTO\UserInfo;
use App\Exception\NoLoggedUserException;
use App\Security\User;
use App\Security\UserTokenService;
use App\Service\Back\GetUserInfoService;
use League\OAuth2\Client\Token\AccessToken;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * @internal
 */
#[CoversClass(GetUserInfoService::class)]
final class GetUserInfoServiceTest extends TestCase
{
    public const ENDPOINT = 'user';
    public const RESPONSE_CONTENT = '/app/tests/resources/unit/service/back/user.json';

    public function testGet(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects($this->exactly(2))
            ->method('info')
        ;

        $client = $this->createMock(HttpClientInterface::class);

        $json = (string) file_get_contents('/app/tests/resources/unit/service/back/user.json');

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
                'https://api.domain/user',
                [
                    'headers' => [
                        'accept' => 'application/json',
                        'Authorization' => 'Bearer abcde-access-token-abcde',
                        'X-Provider' => 'testprovider',
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

        $userInfo = new UserInfo(
            '68464686dazazda6876a3z8d7az0',
            'mock',
            'collector',
            ['ROLE_TRAINER', 'ROLE_COLLECTOR'],
        );

        $serializer = $this->createMock(SerializerInterface::class);
        $serializer
            ->expects($this->once())
            ->method('deserialize')
            ->with($json, UserInfo::class, 'json')
            ->willReturn($userInfo)
        ;

        $service = new GetUserInfoService(
            $logger,
            $client,
            'https://api.domain',
            './resources/certificates/cacert.pem',
            $userTokenService,
            $serializer,
        );

        $accessToken = new AccessToken(['access_token' => 'abcde-access-token-abcde']);

        $userInfo = $service->get($accessToken, 'FakeProvider');

        $this->assertSame('68464686dazazda6876a3z8d7az0', $userInfo->getId());
        $this->assertSame('mock', $userInfo->getProvider());
        $this->assertSame('collector', $userInfo->getProfile());
        $this->assertSame(['ROLE_TRAINER', 'ROLE_COLLECTOR'], $userInfo->getRoles());
    }

    public function testGetWithoutLoggedUser(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects($this->exactly(2))
            ->method('info')
        ;

        $client = $this->createMock(HttpClientInterface::class);

        $json = (string) file_get_contents('/app/tests/resources/unit/service/back/user.json');

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
                'https://api.domain/user',
                [
                    'headers' => [
                        'accept' => 'application/json',
                        'Authorization' => 'Bearer fghjklm-access-token-fghjklm',
                        'X-Provider' => 'fakeprovider',
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

        $userInfo = new UserInfo(
            '68464686dazazda6876a3z8d7az0',
            'mock',
            'collector',
            ['ROLE_TRAINER', 'ROLE_COLLECTOR'],
        );

        $serializer = $this->createMock(SerializerInterface::class);
        $serializer
            ->expects($this->once())
            ->method('deserialize')
            ->with($json, UserInfo::class, 'json')
            ->willReturn($userInfo)
        ;

        $service = new GetUserInfoService(
            $logger,
            $client,
            'https://api.domain',
            './resources/certificates/cacert.pem',
            $userTokenService,
            $serializer,
        );

        $accessToken = new AccessToken(['access_token' => 'fghjklm-access-token-fghjklm']);

        $userInfo = $service->get($accessToken, 'FakeProvider');

        $this->assertSame('68464686dazazda6876a3z8d7az0', $userInfo->getId());
        $this->assertSame('mock', $userInfo->getProvider());
        $this->assertSame('collector', $userInfo->getProfile());
        $this->assertSame(['ROLE_TRAINER', 'ROLE_COLLECTOR'], $userInfo->getRoles());
    }
}
