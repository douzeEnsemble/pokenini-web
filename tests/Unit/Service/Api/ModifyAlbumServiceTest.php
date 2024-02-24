<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Api;

use App\Service\Api\ModifyAlbumService;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class ModifyAlbumServiceTest extends TestCase
{
    private ArrayAdapter $cache;

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

        $this->assertEmpty($this->cache->getValues());
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

        $this->assertEmpty($this->cache->getValues());
    }

    public function testModifyPost(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $client = $this->createMock(HttpClientInterface::class);

        $service = new ModifyAlbumService(
            $client,
            'https://api.domain',
            new ArrayAdapter(),
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
        $client = $this->createMock(HttpClientInterface::class);

        $client
            ->expects($this->once())
            ->method('request')
            ->with(
                $method,
                "https://api.domain/$suffix",
                [
                    'headers' => [
                        'accept' => 'application/json',
                    ],
                    'auth_basic' => [
                        'web',
                        'douze',
                    ],
                    'body' => $body,
                ],
            )
        ;

        $this->cache = new ArrayAdapter();

        return new ModifyAlbumService(
            $client,
            'https://api.domain',
            $this->cache,
            'web',
            'douze',
        );
    }
}
