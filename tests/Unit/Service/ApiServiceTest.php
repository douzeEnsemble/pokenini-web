<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Service\ApiService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class ApiServiceTest extends TestCase
{
    public function testGetDexes(): void
    {
        $service = $this->getService('dexes');

        $this->assertEquals(
            [
                'trucs' => [
                    'bidule',
                    'machin',
                    'chose',
                ],
                'url' => 'dexes',
            ],
            $service->getDexes(),
        );
    }

    public function testGetPokedex(): void
    {
        $service = $this->getService('album/douze');

        $this->assertEquals(
            [
                'trucs' => [
                    'bidule',
                    'machin',
                    'chose',
                ],
                'url' => 'album/douze',
            ],
            $service->getPokedex('douze'),
        );
    }

    public function testGetCatchStates(): void
    {
        $service = $this->getService('catch_states');

        $this->assertEquals(
            [
                'trucs' => [
                    'bidule',
                    'machin',
                    'chose',
                ],
                'url' => 'catch_states',
            ],
            $service->getCatchStates(),
        );
    }

    public function testCache(): void
    {
        $client = $this->createMock(HttpClientInterface::class);

        $response = $this->createMock(ResponseInterface::class);
        $response
            ->expects($this->exactly(4))
            ->method('getContent')
            ->willReturn('{}')
        ;

        $client
            ->expects($this->exactly(4))
            ->method('request')
            ->withConsecutive(
                ['GET', 'api/catch_states'],
                ['GET', 'api/dexes'],
                ['GET', 'api/album/douze'],
                ['GET', 'api/album/treize'],
            )
            ->willReturnOnConsecutiveCalls(
                $response,
                $response,
                $response,
                $response,
            )
        ;

        $cache = new ArrayAdapter();

        $service = new ApiService($client, 'api', $cache);

        $service->getCatchStates();
        $this->assertCount(1, $cache->getValues());
        $this->assertArrayHasKey('catch_states', $cache->getValues());

        $service->getCatchStates();
        $service->getCatchStates();
        $this->assertCount(1, $cache->getValues());
        $this->assertArrayHasKey('catch_states', $cache->getValues());

        $service->getDexes();
        $this->assertCount(2, $cache->getValues());
        $this->assertArrayHasKey('dexes', $cache->getValues());

        $service->getPokedex('douze');
        $this->assertCount(3, $cache->getValues());
        $this->assertArrayHasKey('album_douze', $cache->getValues());

        $service->getPokedex('treize');
        $this->assertCount(4, $cache->getValues());
        $this->assertArrayHasKey('album_treize', $cache->getValues());

        $service->getPokedex('treize');
        $service->getPokedex('treize');
        $service->getPokedex('douze');
        $this->assertCount(4, $cache->getValues());
    }

    public function testInvalidateCaches(): void
    {
        $client = $this->createMock(HttpClientInterface::class);

        $response = $this->createMock(ResponseInterface::class);
        $response
            ->expects($this->exactly(3))
            ->method('getContent')
            ->willReturn('{}')
        ;

        $dexesJson = <<<JSON
        [
          {
            "isShiny": false,
            "isPrivate": true,
            "isDisplayForm": true,
            "name": "Douze",
            "frenchName": "Douze",
            "slug": "douze"
          },
          {
            "isShiny": false,
            "isPrivate": true,
            "isDisplayForm": true,
            "name": "Treize",
            "frenchName": "Treize",
            "slug": "treize"
          }
        ]
        JSON;

        $dexesReponse = $this->createMock(ResponseInterface::class);
        $dexesReponse
            ->expects($this->exactly(2))
            ->method('getContent')
            ->willReturn($dexesJson)
        ;

        $client
            ->expects($this->exactly(5))
            ->method('request')
            ->withConsecutive(
                ['GET', 'api/catch_states'],
                ['GET', 'api/dexes'],
                ['GET', 'api/album/douze'],
                ['GET', 'api/album/treize'],
                ['GET', 'api/dexes'],
            )
            ->willReturnOnConsecutiveCalls(
                $response,
                $dexesReponse,
                $response,
                $response,
                $dexesReponse
            )
        ;

        $cache = new ArrayAdapter();

        $service = new ApiService($client, 'api', $cache);

        $service->getCatchStates();
        $service->getDexes();
        $service->getPokedex('douze');
        $service->getPokedex('treize');

        $this->assertCount(4, $cache->getValues());
        $this->assertArrayHasKey('catch_states', $cache->getValues());
        $this->assertArrayHasKey('dexes', $cache->getValues());
        $this->assertArrayHasKey('album_douze', $cache->getValues());
        $this->assertArrayHasKey('album_treize', $cache->getValues());

        $service->invalidateCacheDexes();
        $this->assertCount(3, $cache->getValues());
        $this->assertArrayNotHasKey('dexes', $cache->getValues());

        $service->invalidateCacheCatchStates();
        $this->assertCount(2, $cache->getValues());
        $this->assertArrayNotHasKey('catch_states', $cache->getValues());

        $service->invalidateCacheAlbums();
        $this->assertCount(1, $cache->getValues());
        $this->assertArrayHasKey('dexes', $cache->getValues());
        $this->assertArrayNotHasKey('album_douze', $cache->getValues());
        $this->assertArrayNotHasKey('album_treize', $cache->getValues());
    }

    public function testModifyAlbum(): void
    {
        $client = $this->createMock(HttpClientInterface::class);

        $client
            ->expects($this->exactly(2))
            ->method('request')
        ;

        $cache = new ArrayAdapter();

        $service = new ApiService($client, 'api', $cache);

        $service->modifyAlbum('PATCH', 'a', 'b', 'c');
        $service->modifyAlbum('PUT', 'a', 'b', 'c');

        $this->expectException(\InvalidArgumentException::class);
        $service->modifyAlbum('POST', 'a', 'b', 'c');
    }

    private function getService(string $url): ApiService
    {
        $client = $this->createMock(HttpClientInterface::class);

        $json = <<<JSON
        {
            "trucs": [
                "bidule",
                "machin",
                "chose"
            ],
            "url": "$url"
        }
        JSON;

        $response = $this->createMock(ResponseInterface::class);
        $response
            ->expects($this->once())
            ->method('getContent')
            ->willReturn($json)
        ;

        $client
            ->expects($this->once())
            ->method('request')
            ->with('GET', "api/$url")
            ->willReturn($response)
        ;

        return new ApiService($client, 'api', new ArrayAdapter());
    }
}
