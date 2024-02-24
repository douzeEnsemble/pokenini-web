<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Api;

use App\Service\Api\ModifyDexService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class ModifyDexServiceTest extends TestCase
{
    private ArrayAdapter $cache;

    public function testModify(): void
    {
        $this
            ->getService(
                'dex/123/home',
                'data-whatever',
            )
            ->modify(
                'home',
                'data-whatever',
                '123',
            )
        ;

        $this->assertEmpty($this->cache->getValues());
    }

    private function getService(
        string $suffix,
        string $body
    ): ModifyDexService {
        $client = $this->createMock(HttpClientInterface::class);

        $client
            ->expects($this->once())
            ->method('request')
            ->with(
                'PUT',
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

        return new ModifyDexService(
            $client,
            'https://api.domain',
            $this->cache,
            'web',
            'douze',
        );
    }
}
