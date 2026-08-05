<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Service\GetResourcesVersionService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpClient\Exception\TransportException;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * @internal
 */
#[CoversClass(GetResourcesVersionService::class)]
final class GetResourcesVersionServiceTest extends TestCase
{
    public function testGetReturnsTrimmedVersion(): void
    {
        $response = $this->createStub(ResponseInterface::class);
        $response->method('getContent')->willReturn("1.9.7\n");

        $client = $this->createMock(HttpClientInterface::class);
        $client
            ->expects($this->once())
            ->method('request')
            ->with('GET', 'https://resources.domain/resources/metadata/version')
            ->willReturn($response)
        ;

        $service = new GetResourcesVersionService($this->createStub(LoggerInterface::class), $client, 'https://resources.domain/resources/metadata/version');

        $this->assertSame('1.9.7', $service->get());
    }

    public function testGetReturnsNullOnTransportError(): void
    {
        $exception = $this->createStub(TransportException::class);

        $client = $this->createStub(HttpClientInterface::class);
        $client->method('request')->willThrowException($exception);

        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects($this->once())
            ->method('warning')
            ->with('Failed to fetch resources version', ['exception' => $exception])
        ;

        $service = new GetResourcesVersionService($logger, $client, 'https://resources.domain/resources/metadata/version');

        $this->assertNull($service->get());
    }

    public function testGetReturnsNullOnHttpError(): void
    {
        $response = $this->createMock(ResponseInterface::class);
        $response
            ->expects($this->once())
            ->method('getContent')
            ->willThrowException($this->createStub(ClientExceptionInterface::class))
        ;

        $client = $this->createStub(HttpClientInterface::class);
        $client->method('request')->willReturn($response);

        $service = new GetResourcesVersionService($this->createStub(LoggerInterface::class), $client, 'https://resources.domain/resources/metadata/version');

        $this->assertNull($service->get());
    }
}
