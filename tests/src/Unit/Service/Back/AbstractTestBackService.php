<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Back;

use App\Exception\NoLoggedUserException;
use App\Security\User;
use App\Security\UserTokenServiceInterface;
use App\Service\Back\AbstractBackService;
use App\Tests\Utils\WithConsecutive;
use League\OAuth2\Client\Token\AccessToken;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

abstract class AbstractTestBackService extends TestCase
{
    /**
     * @param array<int|string, mixed> $requestOptions
     */
    protected function getServiceWithLoggedUser(
        string $method,
        string $responseContent,
        string $endpoint,
        array $requestOptions = [],
        ?SerializerInterface $serializer = null,
    ): AbstractBackService {
        $logger = $this->getLoggerMock(
            $method,
            $responseContent,
            $endpoint,
            'dzdz-access-token-dzdz',
            $requestOptions,
        );
        $client = $this->getClientMock(
            $method,
            $responseContent,
            $endpoint,
            'dzdz-access-token-dzdz',
            $requestOptions,
        );
        $userTokenService = $this->getUserTokenServiceMock('dzdz-access-token-dzdz');

        $serializer ??= $this->createStub(SerializerInterface::class);

        return $this->makeService(
            $logger,
            $client,
            $userTokenService,
            $serializer,
        );
    }

    /**
     * @param array<int|string, mixed> $requestOptions
     */
    protected function getServiceWithoutLoggedUser(
        string $method,
        string $responseContent,
        string $endpoint,
        array $requestOptions = [],
        ?SerializerInterface $serializer = null,
    ): AbstractBackService {
        $logger = $this->getLoggerMock(
            $method,
            $responseContent,
            $endpoint,
            null,
            $requestOptions,
        );
        $client = $this->getClientMock(
            $method,
            $responseContent,
            $endpoint,
            null,
            $requestOptions,
        );
        $userTokenService = $this->getUserTokenServiceMock(null);

        $serializer ??= $this->createStub(SerializerInterface::class);

        return $this->makeService(
            $logger,
            $client,
            $userTokenService,
            $serializer,
        );
    }

    abstract protected function instanciateService(
        LoggerInterface $logger,
        HttpClientInterface $client,
        string $url,
        string $cafilePath,
        UserTokenServiceInterface $userTokenService,
        SerializerInterface $serializer,
    ): AbstractBackService;

    /**
     * @param array<int|string, mixed> $requestOptions
     */
    protected function getLoggerMock(
        string $method,
        string $responseContent,
        string $endpoint,
        ?string $token,
        array $requestOptions = [],
    ): LoggerInterface {
        $headers = [
            'accept' => 'application/json',
        ];
        if (null !== $token) {
            $headers['Authorization'] = '[REDACTED]';
            $headers['X-Provider'] = 'testprovider';
        }

        $messages = [
            [
                "Requesting {$method} /{$endpoint}",
                array_merge(
                    [
                        'headers' => $headers,
                        'cafile' => './resources/certificates/cacert.pem',
                    ],
                    $requestOptions
                ),
            ],
            [
                'Response status code: 200',
                [
                    'response' => substr($responseContent, 0, 256),
                ],
            ],
        ];

        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects($this->exactly(2))
            ->method('info')
            ->with(...WithConsecutive::create(...$messages))
        ;
        $logger
            ->expects($this->once())
            ->method('debug')
            ->with(
                'Response content',
                [
                    'full' => $responseContent,
                ],
            )
        ;

        return $logger;
    }

    protected function getResponseMock(string $content): ResponseInterface
    {
        $nbCalls = empty($content) ? 1 : 2;

        $response = $this->createMock(ResponseInterface::class);
        $response
            ->expects($this->exactly($nbCalls))
            ->method('getContent')
            ->willReturn($content)
        ;
        $response
            ->expects($this->once())
            ->method('getStatusCode')
            ->willReturn(200)
        ;

        return $response;
    }

    /**
     * @param array<int|string, mixed> $requestOptions
     */
    protected function getClientMock(
        string $method,
        string $content,
        string $endpoint,
        ?string $token,
        array $requestOptions = [],
    ): HttpClientInterface {
        $client = $this->createMock(HttpClientInterface::class);

        $response = $this->getResponseMock($content);

        $headers = [
            'accept' => 'application/json',
        ];

        if (null !== $token) {
            // The logged-in path now sends the internal session token as bearer, not the raw
            // provider access token — see AbstractBackService::request() and User::getSessionToken().
            $headers['Authorization'] = 'Bearer test-session-token';
            $headers['X-Provider'] = 'testprovider';
        }

        $client
            ->expects($this->once())
            ->method('request')
            ->with(
                $method,
                'https://api.domain/'.$endpoint,
                array_merge(
                    [
                        'headers' => $headers,
                        'cafile' => './resources/certificates/cacert.pem',
                    ],
                    $requestOptions,
                ),
            )
            ->willReturn($response)
        ;

        return $client;
    }

    protected function getUserTokenServiceMock(?string $token): UserTokenServiceInterface
    {
        $userTokenService = $this->createMock(UserTokenServiceInterface::class);

        if (null === $token) {
            $userTokenService
                ->expects($this->once())
                ->method('getLoggedUser')
                ->willThrowException(new NoLoggedUserException('No user logged'))
            ;

            return $userTokenService;
        }

        $user = new User(
            '12',
            'TestProvider',
            new AccessToken(['access_token' => $token]),
            'test-session-token',
        );

        $userTokenService
            ->expects($this->once())
            ->method('getLoggedUser')
            ->willReturn($user)
        ;

        return $userTokenService;
    }

    protected function makeService(
        LoggerInterface $logger,
        HttpClientInterface $client,
        UserTokenServiceInterface $userTokenService,
        SerializerInterface $serializer,
    ): AbstractBackService {
        return $this->instanciateService(
            $logger,
            $client,
            'https://api.domain',
            './resources/certificates/cacert.pem',
            $userTokenService,
            $serializer,
        );
    }
}
