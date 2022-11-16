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
        $service = $this->getService('dex/7b52009b64fd0a2a49e6d8a939753077792b0554/list');

        $this->assertEquals(
            [
                'trucs' => [
                    'bidule',
                    'machin',
                    'chose',
                ],
                'url' => 'dex/7b52009b64fd0a2a49e6d8a939753077792b0554/list',
            ],
            $service->getDexes('7b52009b64fd0a2a49e6d8a939753077792b0554'),
        );
    }

    public function testGetPokedex(): void
    {
        $service = $this->getService('7b52009b64fd0a2a49e6d8a939753077792b0554/album/douze');

        $this->assertEquals(
            [
                'trucs' => [
                    'bidule',
                    'machin',
                    'chose',
                ],
                'url' => '7b52009b64fd0a2a49e6d8a939753077792b0554/album/douze',
            ],
            $service->getPokedex('douze', '7b52009b64fd0a2a49e6d8a939753077792b0554'),
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
                ['GET', 'api/dex/7b52009b64fd0a2a49e6d8a939753077792b0554/list'],
                ['GET', 'api/7b52009b64fd0a2a49e6d8a939753077792b0554/album/douze'],
                ['GET', 'api/7b52009b64fd0a2a49e6d8a939753077792b0554/album/treize'],
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

        $service->getDexes('7b52009b64fd0a2a49e6d8a939753077792b0554');
        $this->assertCount(3, $cache->getValues());
        $this->assertArrayHasKey('dexes_7b52009b64fd0a2a49e6d8a939753077792b0554', $cache->getValues());
        $this->assertArrayHasKey('register_dexes', $cache->getValues());

        $service->getPokedex('douze', '7b52009b64fd0a2a49e6d8a939753077792b0554');
        $this->assertCount(5, $cache->getValues());
        $this->assertArrayHasKey('album_douze_7b52009b64fd0a2a49e6d8a939753077792b0554', $cache->getValues());
        $this->assertArrayHasKey('register_album', $cache->getValues());

        $service->getPokedex('treize', '7b52009b64fd0a2a49e6d8a939753077792b0554');
        $this->assertCount(6, $cache->getValues());
        $this->assertArrayHasKey('album_treize_7b52009b64fd0a2a49e6d8a939753077792b0554', $cache->getValues());
        $this->assertArrayHasKey('register_album', $cache->getValues());

        $service->getPokedex('treize', '7b52009b64fd0a2a49e6d8a939753077792b0554');
        $service->getPokedex('treize', '7b52009b64fd0a2a49e6d8a939753077792b0554');
        $service->getPokedex('douze', '7b52009b64fd0a2a49e6d8a939753077792b0554');
        $this->assertCount(6, $cache->getValues());
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
            ->expects($this->once())
            ->method('getContent')
            ->willReturn($dexesJson)
        ;

        $client
            ->expects($this->exactly(4))
            ->method('request')
            ->withConsecutive(
                ['GET', 'api/catch_states'],
                ['GET', 'api/dex/7b52009b64fd0a2a49e6d8a939753077792b0554/list'],
                ['GET', 'api/7b52009b64fd0a2a49e6d8a939753077792b0554/album/douze'],
                ['GET', 'api/7b52009b64fd0a2a49e6d8a939753077792b0554/album/treize'],
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
        $service->getDexes('7b52009b64fd0a2a49e6d8a939753077792b0554');
        $service->getPokedex('douze', '7b52009b64fd0a2a49e6d8a939753077792b0554');
        $service->getPokedex('treize', '7b52009b64fd0a2a49e6d8a939753077792b0554');

        $this->assertCount(6, $cache->getValues());
        $this->assertArrayHasKey('catch_states', $cache->getValues());
        $this->assertArrayHasKey('dexes_7b52009b64fd0a2a49e6d8a939753077792b0554', $cache->getValues());
        $this->assertArrayHasKey('album_douze_7b52009b64fd0a2a49e6d8a939753077792b0554', $cache->getValues());
        $this->assertArrayHasKey('album_treize_7b52009b64fd0a2a49e6d8a939753077792b0554', $cache->getValues());
        $this->assertArrayHasKey('register_dexes', $cache->getValues());
        $this->assertArrayHasKey('register_album', $cache->getValues());

        $service->invalidateCacheDexes();
        $this->assertCount(4, $cache->getValues());
        $this->assertArrayNotHasKey('dexes_7b52009b64fd0a2a49e6d8a939753077792b0554', $cache->getValues());
        $this->assertArrayNotHasKey('register_dexes', $cache->getValues());

        $service->invalidateCacheCatchStates();
        $this->assertCount(3, $cache->getValues());
        $this->assertArrayNotHasKey('catch_states', $cache->getValues());

        $service->invalidateCacheAlbums();
        $this->assertCount(0, $cache->getValues());
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

        $service->modifyAlbum('PATCH', 'a', 'b', 'c', '7b52009b64fd0a2a49e6d8a939753077792b0554');
        $service->modifyAlbum('PUT', 'a', 'b', 'c', '7b52009b64fd0a2a49e6d8a939753077792b0554');

        $this->expectException(\InvalidArgumentException::class);
        $service->modifyAlbum('POST', 'a', 'b', 'c', '7b52009b64fd0a2a49e6d8a939753077792b0554');
    }

    public function testModifyDex(): void
    {
        $client = $this->createMock(HttpClientInterface::class);

        $client
            ->expects($this->exactly(1))
            ->method('request')
        ;

        $cache = new ArrayAdapter();

        $service = new ApiService($client, 'api', $cache);

        $service->modifyDex('a', '{"b": "c"}', '7b52009b64fd0a2a49e6d8a939753077792b0554');
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
