<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Api;

use App\Service\Api\ModifyDexService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Adapter\TagAwareAdapter;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * @internal
 */
#[CoversClass(ModifyDexService::class)]
class ModifyDexServiceTest extends TestCase
{
    private ArrayAdapter $cachePool;
    private TagAwareAdapter $cache;

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

        $this->assertEmpty($this->cachePool->getValues());
    }

    private function getService(
        string $suffix,
        string $body
    ): ModifyDexService {
        $logger = $this->createMock(LoggerInterface::class);

        $client = $this->createMock(HttpClientInterface::class);

        $client
            ->expects($this->once())
            ->method('request')
            ->with(
                'PUT',
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

        return new ModifyDexService(
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
