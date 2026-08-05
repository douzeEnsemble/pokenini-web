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
        $versions = $this->getServiceWithResponseBody('{"back":"1.2.12","api":"1.2.13"}')->get();

        $this->assertSame('1.2.12', $versions->back);
        $this->assertSame('1.2.13', $versions->api);
    }

    public function testGetHandlesNullApiField(): void
    {
        $versions = $this->getServiceWithResponseBody('{"back":"1.2.12","api":null}')->get();

        $this->assertSame('1.2.12', $versions->back);
        $this->assertNull($versions->api);
    }

    public function testGetReturnsNullFieldsOnTransportError(): void
    {
        $client = $this->createStub(HttpClientInterface::class);
        $client->method('request')->willThrowException(
            $this->createStub(TransportException::class)
        );

        $versions = $this->buildService($client)->get();

        $this->assertNull($versions->back);
        $this->assertNull($versions->api);
    }

    /**
     * Reproduces the real production failure mode: the container-wired serializer (with a
     * PropertyInfo type extractor, as configured in config/packages/serializer.yaml) throws
     * Symfony\Component\Serializer\Exception\NotNormalizableValueException — not \TypeError —
     * when a field's JSON type doesn't match the declared PHP type. This was verified by
     * booting the real test kernel and deserializing the same malformed body.
     */
    public function testGetReturnsNullFieldsOnNotNormalizableValue(): void
    {
        $versions = $this->getServiceWithResponseBody('{"back":{"x":1},"api":"1.0"}')->get();

        $this->assertNull($versions->back);
        $this->assertNull($versions->api);
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
