<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\DTO\PokemonImage;
use App\Service\ShinyGameImageService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpClient\Exception\TransportException;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * @internal
 */
#[CoversClass(ShinyGameImageService::class)]
#[CoversClass(PokemonImage::class)]
final class ShinyGameImageServiceTest extends TestCase
{
    private const string URL = 'https://resources.domain/pokemon/big/%1$s/%2$s.webp';

    #[Test]
    public function getRegular(): void
    {
        $service = new ShinyGameImageService(
            $this->createStub(LoggerInterface::class),
            $this->getClient('https://resources.domain/pokemon/big/regular/ivysaur.webp', 'regular', ['content-type' => ['image/webp']]),
            self::URL,
        );

        $image = $service->get('ivysaur', false);

        $this->assertNotNull($image);
        $this->assertSame('regular', $image->getContent());
        $this->assertSame('image/webp', $image->getContentType());
    }

    #[Test]
    public function getShiny(): void
    {
        $service = new ShinyGameImageService(
            $this->createStub(LoggerInterface::class),
            $this->getClient('https://resources.domain/pokemon/big/shiny/raichu-alola.webp', 'shiny', ['content-type' => ['image/webp']]),
            self::URL,
        );

        $image = $service->get('raichu-alola', true);

        $this->assertNotNull($image);
        $this->assertSame('shiny', $image->getContent());
    }

    #[Test]
    public function getWithoutContentType(): void
    {
        $service = new ShinyGameImageService(
            $this->createStub(LoggerInterface::class),
            $this->getClient('https://resources.domain/pokemon/big/regular/ivysaur.webp', 'regular', []),
            self::URL,
        );

        $this->assertSame('application/octet-stream', $service->get('ivysaur', false)?->getContentType());
    }

    #[Test]
    public function getOnTransportError(): void
    {
        $exception = $this->createStub(TransportException::class);

        $client = $this->createStub(HttpClientInterface::class);
        $client->method('request')->willThrowException($exception);

        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects($this->once())
            ->method('warning')
            ->with('Failed to fetch the shiny game image', ['exception' => $exception])
        ;

        $service = new ShinyGameImageService($logger, $client, self::URL);

        $this->assertNull($service->get('ivysaur', false));
    }

    #[Test]
    public function getOnHttpError(): void
    {
        $response = $this->createMock(ResponseInterface::class);
        $response
            ->expects($this->once())
            ->method('getContent')
            ->willThrowException($this->createStub(ClientExceptionInterface::class))
        ;

        $client = $this->createStub(HttpClientInterface::class);
        $client->method('request')->willReturn($response);

        $service = new ShinyGameImageService($this->createStub(LoggerInterface::class), $client, self::URL);

        $this->assertNull($service->get('ivysaur', false));
    }

    /**
     * @param array<string, list<string>> $headers
     */
    private function getClient(string $url, string $content, array $headers): HttpClientInterface
    {
        $response = $this->createStub(ResponseInterface::class);
        $response->method('getContent')->willReturn($content);
        $response->method('getHeaders')->willReturn($headers);

        $client = $this->createMock(HttpClientInterface::class);
        $client
            ->expects($this->once())
            ->method('request')
            ->with('GET', $url)
            ->willReturn($response)
        ;

        return $client;
    }
}
