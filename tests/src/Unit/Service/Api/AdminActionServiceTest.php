<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Api;

use App\Service\Api\AdminActionService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Adapter\TagAwareAdapter;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * @internal
 */
#[CoversClass(AdminActionService::class)]
class AdminActionServiceTest extends TestCase
{
    private ArrayAdapter $cachePool;
    private TagAwareAdapter $cache;

    public function testUpdate(): void
    {
        $json = <<<'JSON'
            {
                "suffix": "update/start"
            }
            JSON;

        $this->assertEquals(
            $json,
            $this->getService('update/start')->update('start')
        );

        $this->assertEmpty($this->cachePool->getValues());
    }

    public function testCalculate(): void
    {
        $json = <<<'JSON'
            {
                "suffix": "calculate/start"
            }
            JSON;

        $this->assertEquals(
            $json,
            $this->getService('calculate/start')->calculate('start')
        );

        $this->assertEmpty($this->cachePool->getValues());
    }

    private function getService(string $suffix): AdminActionService
    {
        $json = <<<JSON
            {
                "suffix": "{$suffix}"
            }
            JSON;

        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects($this->exactly(2))
            ->method('info')
        ;

        $client = $this->createMock(HttpClientInterface::class);

        $response = $this->createMock(ResponseInterface::class);
        $response
            ->expects($this->exactly(2))
            ->method('getContent')
            ->willReturn($json)
        ;

        $client
            ->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                "https://api.domain/istration/{$suffix}",
                [
                    'headers' => [
                        'accept' => 'application/json',
                    ],
                    'auth_basic' => [
                        'web',
                        'douze',
                    ],
                    'cafile' => './resources/certificates/cacert.pem',
                ],
            )
            ->willReturn($response)
        ;

        $this->cachePool = new ArrayAdapter();
        $this->cache = new TagAwareAdapter($this->cachePool, new ArrayAdapter());

        return new AdminActionService(
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
