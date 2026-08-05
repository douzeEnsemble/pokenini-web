<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Back;

use App\Exception\NoLoggedUserException;
use App\Security\UserTokenServiceInterface;
use App\Service\Back\GetVersionsService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpClient\Exception\TransportException;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactory;
use Symfony\Component\Serializer\Mapping\Loader\AttributeLoader;
use Symfony\Component\Serializer\NameConverter\MetadataAwareNameConverter;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\SerializerInterface;
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
        $client = $this->createMock(HttpClientInterface::class);
        $client->method('request')->willThrowException(
            $this->createMock(TransportException::class)
        );

        $versions = $this->buildService($client)->get();

        $this->assertNull($versions->back);
        $this->assertNull($versions->api);
    }

    private function getServiceWithResponseBody(string $responseBody): GetVersionsService
    {
        $response = $this->createStub(ResponseInterface::class);
        $response->method('getContent')->willReturn($responseBody);

        $client = $this->createMock(HttpClientInterface::class);
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
            $this->buildSerializer(),
        );
    }

    private function buildSerializer(): SerializerInterface
    {
        $classMetadataFactory = new ClassMetadataFactory(new AttributeLoader());
        $nameConverter = new MetadataAwareNameConverter($classMetadataFactory);

        return new Serializer(
            [
                new DateTimeNormalizer(),
                new ObjectNormalizer($classMetadataFactory, $nameConverter),
            ],
            [new JsonEncoder()]
        );
    }
}
