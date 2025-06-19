<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Api;

use App\Service\Api\ElectionTopApiService;
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
#[CoversClass(ElectionTopApiService::class)]
class ElectionTopApiServiceTest extends TestCase
{
    private ArrayAdapter $cachePool;
    private TagAwareAdapter $cache;

    public function testGet(): void
    {
        $items = $this->getService('4564650', 'home', 'fav', 5)->getTop('4564650', 'home', 'fav', 5);

        $this->assertCount(5, $items);

        $this->assertEmpty($this->cachePool->getValues());
    }

    public function testGetBis(): void
    {
        $items = $this->getService('87654', 'demo', 'pref', 10)->getTop('87654', 'demo', 'pref', 10);

        $this->assertCount(10, $items);

        $this->assertEmpty($this->cachePool->getValues());
    }

    private function getService(
        string $trainerId,
        string $dexSlug,
        string $electionSlug,
        int $count,
    ): ElectionTopApiService {
        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects($this->exactly(2))
            ->method('info')
        ;

        $client = $this->createMock(HttpClientInterface::class);

        $json = (string) file_get_contents("/var/www/html/tests/resources/unit/service/api/election_top_{$count}_{$trainerId}_{$dexSlug}_{$electionSlug}.json");

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
                'GET',
                "https://api.domain/election/top?trainer_external_id={$trainerId}&dex_slug={$dexSlug}&election_slug={$electionSlug}&count={$count}",
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

        return new ElectionTopApiService(
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
