<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Service\GetResourcesVersionService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
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

        $service = new GetResourcesVersionService($client, 'https://resources.domain/resources/metadata/version');

        $this->assertSame('1.9.7', $service->get());
    }

    public function testGetReturnsNullOnTransportError(): void
    {
        $client = $this->createMock(HttpClientInterface::class);
        $client->method('request')->willThrowException(
            $this->createMock(TransportException::class)
        );

        $service = new GetResourcesVersionService($client, 'https://resources.domain/resources/metadata/version');

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

        $client = $this->createMock(HttpClientInterface::class);
        $client->method('request')->willReturn($response);

        $service = new GetResourcesVersionService($client, 'https://resources.domain/resources/metadata/version');

        $this->assertNull($service->get());
    }
}
