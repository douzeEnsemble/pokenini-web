<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Back;

use App\Security\UserTokenService;
use App\Service\Back\BackServiceInterface;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

trait BackServiceTrait
{
    /**
     * @param array<int|string, mixed> $requestOptions
     */
    public function getServiceWithLoggedUser(
        string $className,
        string $method,
        string $responseContent,
        string $endpoint,
        array $requestOptions = [],
    ): BackServiceInterface {
        $logger = $this->getLoggerMock();
        $client = $this->getClientMock(
            $method,
            $responseContent,
            $endpoint,
            'dzdz-access-token-dzdz',
            $requestOptions,
        );
        $userTokenService = $this->getUserTokenServiceMock('dzdz-access-token-dzdz');

        return $this->instanciateService(
            $className,
            $logger,
            $client,
            $userTokenService,
        );
    }

    /**
     * @param array<int|string, mixed> $requestOptions
     */
    public function getServiceWithoutLoggedUser(
        string $className,
        string $method,
        string $responseContent,
        string $endpoint,
        array $requestOptions = [],
    ): BackServiceInterface {
        $logger = $this->getLoggerMock();
        $client = $this->getClientMock(
            $method,
            $responseContent,
            $endpoint,
            'public',
            $requestOptions,
        );
        $userTokenService = $this->getUserTokenServiceMock('public');

        return $this->instanciateService(
            $className,
            $logger,
            $client,
            $userTokenService,
        );
    }

    private function getLoggerMock(): LoggerInterface
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects($this->exactly(2))
            ->method('info')
        ;

        return $logger;
    }

    private function getResponseMock(string $content): ResponseInterface
    {
        $nbCalls = empty($content) ? 1 : 2;

        $response = $this->createMock(ResponseInterface::class);
        $response
            ->expects($this->exactly($nbCalls))
            ->method('getContent')
            ->willReturn($content)
        ;

        return $response;
    }

    /**
     * @param array<int|string, mixed> $requestOptions
     */
    private function getClientMock(
        string $method,
        string $content,
        string $endpoint,
        string $token,
        array $requestOptions = [],
    ): HttpClientInterface {
        $client = $this->createMock(HttpClientInterface::class);

        $response = $this->getResponseMock($content);

        $client
            ->expects($this->once())
            ->method('request')
            ->with(
                $method,
                'https://api.domain/'.$endpoint,
                array_merge(
                    [
                        'headers' => [
                            'accept' => 'application/json',
                            'Authorization' => 'Bearer '.$token,
                        ],
                        'cafile' => './resources/certificates/cacert.pem',
                    ],
                    $requestOptions,
                ),
            )
            ->willReturn($response)
        ;

        return $client;
    }

    private function getUserTokenServiceMock(string $token): UserTokenService
    {
        $userTokenService = $this->createMock(UserTokenService::class);
        $userTokenService
            ->expects($this->once())
            ->method('getLoggedUserToken')
            ->willReturn($token)
        ;

        return $userTokenService;
    }

    private function instanciateService(
        string $className,
        LoggerInterface $logger,
        HttpClientInterface $client,
        UserTokenService $userTokenService,
    ): BackServiceInterface {
        /** @var BackServiceInterface */
        return new $className(
            $logger,
            $client,
            'https://api.domain',
            './resources/certificates/cacert.pem',
            $userTokenService,
        );
    }
}
