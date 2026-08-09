<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Back;

use App\Security\User;
use App\Security\UserTokenServiceInterface;
use App\Service\Back\GetVersionsService;
use App\Tests\Utils\RealSerializerFactory;
use League\OAuth2\Client\Token\AccessToken;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpClient\Exception\TransportException;
use Symfony\Component\Serializer\Exception\ExceptionInterface as SerializerExceptionInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * @internal
 */
#[CoversClass(GetVersionsService::class)]
final class GetVersionsServiceTest extends TestCase
{
    #[Test]
    public function getDeserializesVersions(): void
    {
        $versions = $this->getServiceWithResponseBody(
            '{"back":{"version":"1.2.12","updated_at":"2026-08-05T09:12:00+00:00"},"api":{"version":"1.2.13","updated_at":"2026-08-04T21:47:00+00:00"}}'
        )->get();

        $this->assertSame('1.2.12', $versions->back->version);
        $this->assertSame('2026-08-05T09:12:00+00:00', $versions->back->updatedAt?->format(\DateTimeInterface::ATOM));
        $this->assertSame('1.2.13', $versions->api->version);
        $this->assertSame('2026-08-04T21:47:00+00:00', $versions->api->updatedAt?->format(\DateTimeInterface::ATOM));
    }

    #[Test]
    public function getHandlesNullApiField(): void
    {
        $versions = $this->getServiceWithResponseBody(
            '{"back":{"version":"1.2.12","updated_at":"2026-08-05T09:12:00+00:00"},"api":{"version":null,"updated_at":null}}'
        )->get();

        $this->assertSame('1.2.12', $versions->back->version);
        $this->assertNull($versions->api->version);
        $this->assertNull($versions->api->updatedAt);
    }

    #[Test]
    public function getReturnsNullFieldsOnTransportError(): void
    {
        $client = $this->createMock(HttpClientInterface::class);
        $client
            ->expects($this->once())
            ->method('request')
            ->willThrowException(
                $this->createStub(TransportException::class)
            )
        ;

        $versions = $this->buildService($client)->get();

        $this->assertNull($versions->back->version);
        $this->assertNull($versions->back->updatedAt);
        $this->assertNull($versions->api->version);
        $this->assertNull($versions->api->updatedAt);
    }

    #[Test]
    public function getReturnsNullFieldsOnSerializeError(): void
    {
        $userTokenService = $this->buildUserTokenService();

        $response = $this->createMock(ResponseInterface::class);
        $response
            ->expects($this->exactly(2))
            ->method('getContent')
            ->willReturn('whatever')
        ;

        $client = $this->createMock(HttpClientInterface::class);
        $client
            ->expects($this->once())
            ->method('request')
            ->willReturn($response)
        ;

        $serializer = $this->createMock(SerializerInterface::class);
        $serializer
            ->expects($this->once())
            ->method('deserialize')
            ->with('whatever', 'App\ResponseObject\Versions', 'json')
            ->willThrowException(
                $this->createStub(SerializerExceptionInterface::class)
            )
        ;

        $service = new GetVersionsService(
            $this->createStub(LoggerInterface::class),
            $client,
            'https://back.domain',
            './resources/certificates/cacert.pem',
            $userTokenService,
            $serializer,
        );

        $versions = $service->get();

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
    #[Test]
    public function getReturnsNullFieldsOnNotNormalizableValue(): void
    {
        $versions = $this->getServiceWithResponseBody(
            '{"back":{"x":1},"api":{"version":null,"updated_at":null}}'
        )
            ->get()
        ;

        $this->assertNull($versions->back->version);
        $this->assertNull($versions->api->version);
    }

    private function getServiceWithResponseBody(string $responseBody): GetVersionsService
    {
        $response = $this->createMock(ResponseInterface::class);
        $response
            ->expects($this->exactly(2))
            ->method('getContent')
            ->willReturn($responseBody)
        ;

        $client = $this->createMock(HttpClientInterface::class);
        $client
            ->expects($this->once())
            ->method('request')
            ->willReturn($response)
        ;

        return $this->buildService($client);
    }

    private function buildService(HttpClientInterface $client): GetVersionsService
    {
        $userTokenService = $this->buildUserTokenService();

        return new GetVersionsService(
            $this->createStub(LoggerInterface::class),
            $client,
            'https://back.domain',
            './resources/certificates/cacert.pem',
            $userTokenService,
            RealSerializerFactory::create(),
        );
    }

    private function buildUserTokenService(): UserTokenServiceInterface
    {
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

        return $userTokenService;
    }
}
