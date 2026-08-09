<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Back;

use App\Exception\NoLoggedUserException;
use App\ResponseObject\Election\ElectionIndex;
use App\Security\UserTokenServiceInterface;
use App\Service\Back\AbstractBackService;
use App\Service\Back\GetElectionIndexService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
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

    #[Test]
    public function getWithElectionSlug(): void
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

    #[Test]
    public function getWithoutElectionSlug(): void
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

    #[Test]
    public function clientException(): void
    {
        $serializer = $this->createStub(SerializerInterface::class);

        $client = $this->createMock(HttpClientInterface::class);
        $client
            ->expects($this->once())
            ->method('request')
            ->willThrowException($this->createStub(ClientExceptionInterface::class))
        ;

        $logger = $this->createStub(LoggerInterface::class);

        $userTokenService = $this->createMock(UserTokenServiceInterface::class);
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
        UserTokenServiceInterface $userTokenService,
        SerializerInterface $serializer,
    ): AbstractBackService {
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
                'view_count' => ['sum' => 150, 'max' => 100],
                'win_count' => ['sum' => 100, 'max' => 100],
                'completion' => ['under_max_count' => 100, 'at_max_count' => 100],
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
