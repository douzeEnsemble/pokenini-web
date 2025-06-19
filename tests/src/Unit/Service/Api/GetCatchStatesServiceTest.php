<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Api;

use App\Service\Api\GetCatchStatesService;
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
#[CoversClass(GetCatchStatesService::class)]
class GetCatchStatesServiceTest extends TestCase
{
    private ArrayAdapter $cachePool;
    private TagAwareAdapter $cache;

    public function testGet(): void
    {
        $expectedResult = [
            [
                'name' => 'No',
                'frenchName' => 'Non',
                'slug' => 'no',
                'color' => '#e57373',
            ],
            [
                'name' => 'To evolve',
                'frenchName' => 'af. évoluer',
                'slug' => 'toevolve',
                'color' => '#9575cd',
            ],
            [
                'name' => 'To breed',
                'frenchName' => 'af. reproduire',
                'slug' => 'tobreed',
                'color' => '#4fc3f7',
            ],
            [
                'name' => 'To transfer',
                'frenchName' => 'à transférer',
                'slug' => 'totransfer',
                'color' => '#ffd54f',
            ],
            [
                'name' => 'To trade',
                'frenchName' => 'À échanger',
                'slug' => 'totrade',
                'color' => '#ff9100',
            ],
            [
                'name' => 'Yes',
                'frenchName' => 'Oui',
                'slug' => 'yes',
                'color' => '#66bb6a',
            ],
        ];

        $this->assertEquals(
            $expectedResult,
            $this->getService()->get(),
        );

        /** @var string $value */
        $value = $this->cache->getItem('catch_states')->get();

        $this->assertJsonStringEqualsJsonString(
            (string) json_encode($expectedResult),
            $value,
        );
    }

    private function getService(): GetCatchStatesService
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects($this->exactly(2))
            ->method('info')
        ;

        $client = $this->createMock(HttpClientInterface::class);

        $json = (string) file_get_contents('/var/www/html/tests/resources/unit/service/api/catch_states.json');

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
                'https://api.domain/catch_states',
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

        return new GetCatchStatesService(
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
