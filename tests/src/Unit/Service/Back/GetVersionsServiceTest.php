<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Back;

use App\Exception\NoLoggedUserException;
use App\Security\UserTokenServiceInterface;
use App\Service\Back\GetVersionsService;
use App\Tests\Utils\RealSerializerFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpClient\Exception\TransportException;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * @internal
 */
#[CoversClass(GetVersionsService::class)]
final class GetVersionsServiceTest extends TestCase
{
    public function testGetDeserializesVersions(): void
    {
        $versions = $this->getServiceWithResponseBody(
            '{"back":{"version":"1.2.12","updated_at":"2026-08-05T09:12:00+00:00"},"api":{"version":"1.2.13","updated_at":"2026-08-04T21:47:00+00:00"}}'
        )->get();

        $this->assertSame('1.2.12', $versions->back->version);
        $this->assertSame('2026-08-05T09:12:00+00:00', $versions->back->updatedAt?->format(\DateTimeInterface::ATOM));
        $this->assertSame('1.2.13', $versions->api->version);
        $this->assertSame('2026-08-04T21:47:00+00:00', $versions->api->updatedAt?->format(\DateTimeInterface::ATOM));
    }

    public function testGetHandlesNullApiField(): void
    {
        $versions = $this->getServiceWithResponseBody(
            '{"back":{"version":"1.2.12","updated_at":"2026-08-05T09:12:00+00:00"},"api":{"version":null,"updated_at":null}}'
        )->get();

        $this->assertSame('1.2.12', $versions->back->version);
        $this->assertNull($versions->api->version);
        $this->assertNull($versions->api->updatedAt);
    }

    public function testGetReturnsNullFieldsOnTransportError(): void
    {
        $client = $this->createStub(HttpClientInterface::class);
        $client->method('request')->willThrowException(
            $this->createStub(TransportException::class)
        );

        $versions = $this->buildService($client)->get();

        $this->assertNull($versions->back->version);
        $this->assertNull($versions->back->updatedAt);
        $this->assertNull($versions->api->version);
        $this->assertNull($versions->api->updatedAt);
    }

    /**
     * Reproduces the real production failure mode: the container-wired serializer (with a
     * PropertyInfo type extractor, as configured in config/packages/serializer.yaml) throws
     * Symfony\Component\Serializer\Exception\NotNormalizableValueException — not \TypeError —
     * when a field's JSON type doesn't match the declared PHP type.
     */
    public function testGetReturnsNullFieldsOnNotNormalizableValue(): void
    {
        $versions = $this->getServiceWithResponseBody('{"back":{"x":1},"api":{"version":"1.0","updated_at":null}}')->get();

        $this->assertNull($versions->back->version);
        $this->assertNull($versions->api->version);
    }

    private function getServiceWithResponseBody(string $responseBody): GetVersionsService
    {
        $response = $this->createStub(ResponseInterface::class);
        $response->method('getContent')->willReturn($responseBody);

        $client = $this->createStub(HttpClientInterface::class);
        $client->method('request')->willReturn($response);

        return $this->buildService($client);
    }

    private function buildService(HttpClientInterface $client): GetVersionsService
    {
        $userTokenService = $this->createStub(UserTokenServiceInterface::class);
        $userTokenService
            ->method('getLoggedUser')
            ->willThrowException(new NoLoggedUserException('No user logged'))
        ;

        return new GetVersionsService(
            $this->createStub(LoggerInterface::class),
            $client,
            'https://back.domain',
            './resources/certificates/cacert.pem',
            $userTokenService,
            RealSerializerFactory::create(),
        );
    }
}
