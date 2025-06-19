<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Api;

use App\Service\Api\GetElectionDexService;
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
#[CoversClass(GetElectionDexService::class)]
class GetElectionDexServiceTest extends TestCase
{
    private ArrayAdapter $cachePool;
    private TagAwareAdapter $cache;

    public function testGet(): void
    {
        $expectedSlugs = [
            'homeshiny',
        ];

        $this->assertEquals(
            $expectedSlugs,
            self::extractSlugs($this->getService()->get()),
        );

        $cacheItem = $this->cache->getItem('election_dex');

        /** @var string $value */
        $value = $cacheItem->get();

        /** @var string[][] */
        $jsonData = json_decode($value, true);

        $this->assertEquals(
            $expectedSlugs,
            self::extractSlugs($jsonData),
        );

        $this->assertSame(
            [
                'dex' => 'dex',
                'election_dex' => 'election_dex',
            ],
            $cacheItem->getMetadata()['tags'],
        );
    }

    public function testGetWithPremium(): void
    {
        $expectedSlugs = [
            'home',
            'redgreenblueyellow',
        ];

        $this->assertEquals(
            $expectedSlugs,
            self::extractSlugs($this->getServiceWithPremium()->getWithPremium()),
        );

        $cacheItem = $this->cache->getItem('election_dex_include_premium_dex=1');

        /** @var string $value */
        $value = $cacheItem->get();

        /** @var string[][] */
        $jsonData = json_decode($value, true);

        $this->assertEquals(
            $expectedSlugs,
            self::extractSlugs($jsonData),
        );

        $this->assertSame(
            [
                'dex' => 'dex',
                'election_dex' => 'election_dex',
            ],
            $cacheItem->getMetadata()['tags'],
        );
    }

    public function testGetWithUnreleasedAndPremium(): void
    {
        $expectedSlugs = [
            'home',
            'homeshiny',
            'redgreenblueyellow',
            'redgreenblueyellowshiny',
        ];

        $this->assertEquals(
            $expectedSlugs,
            self::extractSlugs($this->getServiceWithUnreleasedAndPremium()->getWithUnreleasedAndPremium()),
        );

        $cacheItem = $this->cache->getItem('election_dex_include_unreleased_dex=1_include_premium_dex=1');

        /** @var string $value */
        $value = $cacheItem->get();

        /** @var string[][] */
        $jsonData = json_decode($value, true);

        $this->assertEquals(
            $expectedSlugs,
            self::extractSlugs($jsonData),
        );

        $this->assertSame(
            [
                'dex' => 'dex',
                'election_dex' => 'election_dex',
            ],
            $cacheItem->getMetadata()['tags'],
        );
    }

    private function getService(): GetElectionDexService
    {
        $json = (string) file_get_contents(
            '/var/www/html/tests/resources/unit/service/api/election_dex.json'
        );

        return $this->getMockService(
            $json,
            'dex/can_hold_election',
        );
    }

    private function getServiceWithPremium(): GetElectionDexService
    {
        $json = (string) file_get_contents(
            '/var/www/html/tests/resources/unit/service/api/election_dex_premium.json'
        );

        return $this->getMockService(
            $json,
            'dex/can_hold_election?include_premium_dex=1',
        );
    }

    private function getServiceWithUnreleasedAndPremium(): GetElectionDexService
    {
        $json = (string) file_get_contents(
            '/var/www/html/tests/resources/unit/service/api/election_dex_unreleased_and_premium.json'
        );

        return $this->getMockService(
            $json,
            'dex/can_hold_election?include_unreleased_dex=1&include_premium_dex=1',
        );
    }

    private function getMockService(
        string $json,
        string $endpoint,
    ): GetElectionDexService {
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
                'GET',
                "https://api.domain/{$endpoint}",
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

        return new GetElectionDexService(
            $logger,
            $client,
            'https://api.domain',
            './resources/certificates/cacert.pem',
            $this->cache,
            'web',
            'douze',
        );
    }

    /**
     * @param string[][] $items
     *
     * @return string[]
     */
    private static function extractSlugs(array $items): array
    {
        $slugs = [];

        foreach ($items as $item) {
            $slugs[] = $item['slug'];
        }

        return $slugs;
    }
}
