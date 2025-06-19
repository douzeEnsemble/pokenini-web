<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Api;

use App\Service\Api\ModifyAlbumService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Adapter\TagAwareAdapter;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * @internal
 */
#[CoversClass(ModifyAlbumService::class)]
class ModifyAlbumServiceTest extends TestCase
{
    private ArrayAdapter $cachePool;
    private TagAwareAdapter $cache;

    public function testModifyPatch(): void
    {
        $this
            ->getService(
                'PATCH',
                'album/123/home/pikachu',
                'yes',
            )
            ->modify(
                'PATCH',
                'home',
                'pikachu',
                'yes',
                '123',
            )
        ;

        $this->assertEmpty($this->cachePool->getValues());
    }

    public function testModifyPut(): void
    {
        $this
            ->getService(
                'PUT',
                'album/123/home/pikachu',
                'yes',
            )
            ->modify(
                'PUT',
                'home',
                'pikachu',
                'yes',
                '123',
            )
        ;

        $this->assertEmpty($this->cachePool->getValues());
    }

    public function testModifyPost(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $logger = $this->createMock(LoggerInterface::class);

        $client = $this->createMock(HttpClientInterface::class);

        $service = new ModifyAlbumService(
            $logger,
            $client,
            'https://api.domain',
            './resources/certificates/cacert.pem',
            new TagAwareAdapter(new ArrayAdapter(), new ArrayAdapter()),
            'web',
            'douze',
        );

        $service->modify(
            'POST',
            'home',
            'pikachu',
            'yes',
            '123',
        );
    }

    private function getService(
        string $method,
        string $suffix,
        string $body
    ): ModifyAlbumService {
        $logger = $this->createMock(LoggerInterface::class);

        $client = $this->createMock(HttpClientInterface::class);

        $client
            ->expects($this->once())
            ->method('request')
            ->with(
                $method,
                "https://api.domain/{$suffix}",
                [
                    'headers' => [
                        'accept' => 'application/json',
                    ],
                    'auth_basic' => [
                        'web',
                        'douze',
                    ],
                    'cafile' => './resources/certificates/cacert.pem',
                    'body' => $body,
                ],
            )
        ;

        $this->cachePool = new ArrayAdapter();
        $this->cache = new TagAwareAdapter($this->cachePool, new ArrayAdapter());

        return new ModifyAlbumService(
            $logger,
            $client,
            'https://api.domain',
            './resources/certificates/cacert.pem',
            $this->cache,
            'web',
            'douze',
        );
    }
}
