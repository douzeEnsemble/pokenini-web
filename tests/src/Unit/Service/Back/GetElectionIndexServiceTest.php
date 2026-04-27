<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Back;

use App\Exception\NoLoggedUserException;
use App\ResponseObject\Election\ElectionIndex;
use App\Security\UserTokenService;
use App\Service\Back\BackServiceInterface;
use App\Service\Back\GetElectionIndexService;
use PHPUnit\Framework\Attributes\CoversClass;
use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * @internal
 */
#[CoversClass(GetElectionIndexService::class)]
final class GetElectionIndexServiceTest extends AbstractTestBackService
{
    public const RESPONSE_CONTENT = '{"type":"pick"}';

    public function testGetWithElectionSlug(): void
    {
        $serializer = $this->createMock(SerializerInterface::class);
        $electionIndex = $this->getElectionIndex();

        $serializer->expects($this->once())
            ->method('deserialize')
            ->with(self::RESPONSE_CONTENT, ElectionIndex::class, 'json')
            ->willReturn($electionIndex)
        ;

        /** @var GetElectionIndexService $service */
        $service = $this->getServiceWithLoggedUser(
            'GET',
            self::RESPONSE_CONTENT,
            '/election/test-dex/test-election',
            ['query' => ['some' => 'filter']],
            $serializer
        );

        $result = $service->get('test-dex', 'test-election', ['some' => 'filter']);

        $this->assertSame($electionIndex, $result);
    }

    public function testGetWithoutElectionSlug(): void
    {
        $serializer = $this->createMock(SerializerInterface::class);
        $electionIndex = $this->getElectionIndex();

        $serializer->expects($this->once())
            ->method('deserialize')
            ->with(self::RESPONSE_CONTENT, ElectionIndex::class, 'json')
            ->willReturn($electionIndex)
        ;

        /** @var GetElectionIndexService $service */
        $service = $this->getServiceWithLoggedUser(
            'GET',
            self::RESPONSE_CONTENT,
            '/election/test-dex',
            ['query' => []],
            $serializer
        );

        $result = $service->get('test-dex', '', []);

        $this->assertSame($electionIndex, $result);
    }

    public function testClientException(): void
    {
        $serializer = $this->createStub(SerializerInterface::class);

        $client = $this->createMock(HttpClientInterface::class);
        $client
            ->expects($this->once())
            ->method('request')
            ->willThrowException($this->createStub(ClientExceptionInterface::class))
        ;

        $logger = $this->createStub(LoggerInterface::class);

        $userTokenService = $this->createMock(UserTokenService::class);
        $userTokenService
            ->expects($this->once())
            ->method('getLoggedUser')
            ->willThrowException(new NoLoggedUserException('No user logged'))
        ;

        /** @var GetElectionIndexService $service */
        $service = $this->instanciateService(
            $logger,
            $client,
            'https://api.domain',
            './resources/certificates/cacert.pem',
            $userTokenService,
            $serializer
        );

        $this->expectException(ClientExceptionInterface::class);

        // @var GetElectionIndexService $service
        $service->get('test-dex', '', []);
    }

    #[\Override]
    protected function instanciateService(
        LoggerInterface $logger,
        HttpClientInterface $client,
        string $url,
        string $cafilePath,
        UserTokenService $userTokenService,
        SerializerInterface $serializer,
    ): BackServiceInterface {
        return new GetElectionIndexService(
            $logger,
            $client,
            $url,
            $cafilePath,
            $userTokenService,
            $serializer,
        );
    }

    /**
     * Override this from AbstractTestBackService since the endpoint starts with /.
     *
     * @param array<int|string, mixed> $requestOptions
     */
    #[\Override]
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
            $headers['Authorization'] = 'Bearer '.$token;
            $headers['X-Provider'] = 'testprovider';
        }

        $client
            ->expects($this->once())
            ->method('request')
            ->with(
                $method,
                'https://api.domain'.$endpoint,
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

    /**
     * @param array<int|string, mixed> $requestOptions
     */
    #[\Override]
    protected function getLoggerMock(
        string $method,
        string $responseContent,
        string $endpoint,
        ?string $token,
        array $requestOptions = [],
    ): LoggerInterface {
        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects($this->exactly(2))
            ->method('info')
            ->willReturnCallback(function () {})
        ;

        return $logger;
    }

    private function getElectionIndex(): ElectionIndex
    {
        return new ElectionIndex(
            'pick',
            [],
            null,
            [],
            [
                'view_count_sum' => 150,
                'win_count_sum' => 100,
                'view_count_max' => 100,
                'win_count_max' => 100,
                'under_max_view_count' => 100,
                'max_view_count' => 100,
                'dex_total_count' => 150,
                'round_count' => 5,
                'winner_average' => 0.5,
                'total_round_count' => 5,
            ],
            0,
            false,
            false
        );
    }
}
