<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Service\ApiService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class ApiServiceRequestCacheTest extends TestCase
{
    public function testCatchStatesCache(): void
    {
        $client = $this->createMock(HttpClientInterface::class);

        $response = $this->createMock(ResponseInterface::class);
        $response
            ->expects($this->exactly(2))
            ->method('getContent')
            ->willReturn('{}')
        ;

        $client
            ->expects($this->exactly(2))
            ->method('request')
            ->withConsecutive(
                ['GET', 'api/catch_states'],
                // Additionnal call to pollute requests and caches
                ['GET', 'api/reports'],
            )
            ->willReturn($response)
        ;

        $cache = new ArrayAdapter();

        $service = new ApiService($client, 'api', $cache, 'web', 'douze');

        $this->assertEmpty($cache->getValues());

        $service->getCatchStates();
        $this->assertCount(1, $cache->getValues());
        $this->assertArrayHasKey('catch_states', $cache->getValues());

        $service->getCatchStates();
        $service->getCatchStates();
        $this->assertCount(1, $cache->getValues());
        $this->assertArrayHasKey('catch_states', $cache->getValues());

        $service->getReports();
        $this->assertCount(2, $cache->getValues());
        $this->assertArrayHasKey('catch_states', $cache->getValues());

        $service->invalidateCacheCatchStates();
        $this->assertCount(1, $cache->getValues());
        $this->assertArrayNotHasKey('catch_states', $cache->getValues());
    }

    public function testDexCache(): void
    {
        $client = $this->createMock(HttpClientInterface::class);

        $response = $this->createMock(ResponseInterface::class);
        $response
            ->expects($this->exactly(5))
            ->method('getContent')
            ->willReturn('{}')
        ;

        $client
            ->expects($this->exactly(5))
            ->method('request')
            ->withConsecutive(
                ['GET', 'api/dex/7b52009b64fd0a2a49e6d8a939753077792b0554/list'],
                ['GET', 'api/dex/6c33064427a5b419ca8eb3f7d11a0807f66cab22/list'],
                // Additionnal call to pollute requests and caches
                ['GET', 'api/reports'],
                // Additional calls to tests invalidate them all
                ['GET', 'api/dex/7b52009b64fd0a2a49e6d8a939753077792b0554/list'],
                ['GET', 'api/dex/6c33064427a5b419ca8eb3f7d11a0807f66cab22/list'],
            )
            ->willReturn($response)
        ;

        $cache = new ArrayAdapter();

        $service = new ApiService($client, 'api', $cache, 'web', 'douze');

        $this->assertEmpty($cache->getValues());

        $service->getDex('7b52009b64fd0a2a49e6d8a939753077792b0554');
        $this->assertCount(2, $cache->getValues());
        $this->assertArrayHasKey('dex_7b52009b64fd0a2a49e6d8a939753077792b0554', $cache->getValues());
        $this->assertArrayHasKey('register_dex', $cache->getValues());

        $service->getDex('6c33064427a5b419ca8eb3f7d11a0807f66cab22');
        $this->assertCount(3, $cache->getValues());
        $this->assertArrayHasKey('dex_7b52009b64fd0a2a49e6d8a939753077792b0554', $cache->getValues());
        $this->assertArrayHasKey('dex_6c33064427a5b419ca8eb3f7d11a0807f66cab22', $cache->getValues());
        $this->assertArrayHasKey('register_dex', $cache->getValues());

        $service->getReports();
        $this->assertCount(4, $cache->getValues());
        $this->assertArrayHasKey('dex_7b52009b64fd0a2a49e6d8a939753077792b0554', $cache->getValues());
        $this->assertArrayHasKey('dex_6c33064427a5b419ca8eb3f7d11a0807f66cab22', $cache->getValues());
        $this->assertArrayHasKey('register_dex', $cache->getValues());

        $service->invalidateCacheDexByTrainerId('7b52009b64fd0a2a49e6d8a939753077792b0554');
        $this->assertCount(3, $cache->getValues());
        $this->assertArrayNotHasKey('dex_7b52009b64fd0a2a49e6d8a939753077792b0554', $cache->getValues());
        $this->assertArrayHasKey('dex_6c33064427a5b419ca8eb3f7d11a0807f66cab22', $cache->getValues());
        $this->assertArrayHasKey('register_dex', $cache->getValues());
        /** @var string[] $cacheValues */
        $cacheValues = $cache->getValues();
        $this->assertEquals(
            'a:1:{i:0;s:44:"dex_6c33064427a5b419ca8eb3f7d11a0807f66cab22";}',
            $cacheValues['register_dex']
        );

        $service->invalidateCacheDexByTrainerId('6c33064427a5b419ca8eb3f7d11a0807f66cab22');
        $this->assertCount(2, $cache->getValues());
        $this->assertArrayNotHasKey('dex_7b52009b64fd0a2a49e6d8a939753077792b0554', $cache->getValues());
        $this->assertArrayNotHasKey('dex_6c33064427a5b419ca8eb3f7d11a0807f66cab22', $cache->getValues());
        $this->assertArrayHasKey('register_dex', $cache->getValues());
        /** @var string[] $cacheValues */
        $cacheValues = $cache->getValues();
        $this->assertEquals('a:0:{}', $cacheValues['register_dex']);

        $service->getDex('7b52009b64fd0a2a49e6d8a939753077792b0554');
        $service->getDex('6c33064427a5b419ca8eb3f7d11a0807f66cab22');
        $this->assertArrayHasKey('dex_7b52009b64fd0a2a49e6d8a939753077792b0554', $cache->getValues());
        $this->assertArrayHasKey('dex_6c33064427a5b419ca8eb3f7d11a0807f66cab22', $cache->getValues());
        $this->assertArrayHasKey('register_dex', $cache->getValues());

        $service->invalidateCacheDex();
        $this->assertCount(1, $cache->getValues());
        $this->assertArrayNotHasKey('dex_7b52009b64fd0a2a49e6d8a939753077792b0554', $cache->getValues());
        $this->assertArrayNotHasKey('dex_6c33064427a5b419ca8eb3f7d11a0807f66cab22', $cache->getValues());
        $this->assertArrayNotHasKey('register_dex', $cache->getValues());
    }

    public function testAlbumCache(): void
    {
        $client = $this->createMock(HttpClientInterface::class);

        $response = $this->createMock(ResponseInterface::class);
        $response
            ->expects($this->exactly(5))
            ->method('getContent')
            ->willReturn('{}')
        ;

        $client
            ->expects($this->exactly(5))
            ->method('request')
            ->withConsecutive(
                ['GET', 'api/album/7b52009b64fd0a2a49e6d8a939753077792b0554/douze'],
                ['GET', 'api/album/7b52009b64fd0a2a49e6d8a939753077792b0554/treize'],
                // Additionnal call to pollute requests and caches
                ['GET', 'api/reports'],
                // Additional calls to tests invalidate them all
                ['GET', 'api/album/7b52009b64fd0a2a49e6d8a939753077792b0554/douze'],
                ['GET', 'api/album/7b52009b64fd0a2a49e6d8a939753077792b0554/treize'],
            )
            ->willReturn($response)
        ;

        $cache = new ArrayAdapter();

        $service = new ApiService($client, 'api', $cache, 'web', 'douze');

        $this->assertEmpty($cache->getValues());

        $service->getPokedex('douze', '7b52009b64fd0a2a49e6d8a939753077792b0554');
        $this->assertCount(2, $cache->getValues());
        $this->assertArrayHasKey('album_douze_7b52009b64fd0a2a49e6d8a939753077792b0554', $cache->getValues());
        $this->assertArrayHasKey('register_album', $cache->getValues());

        $service->getPokedex('treize', '7b52009b64fd0a2a49e6d8a939753077792b0554');
        $this->assertCount(3, $cache->getValues());
        $this->assertArrayHasKey('album_douze_7b52009b64fd0a2a49e6d8a939753077792b0554', $cache->getValues());
        $this->assertArrayHasKey('album_treize_7b52009b64fd0a2a49e6d8a939753077792b0554', $cache->getValues());
        $this->assertArrayHasKey('register_album', $cache->getValues());

        $service->getReports();
        $this->assertCount(4, $cache->getValues());
        $this->assertArrayHasKey('album_douze_7b52009b64fd0a2a49e6d8a939753077792b0554', $cache->getValues());
        $this->assertArrayHasKey('album_treize_7b52009b64fd0a2a49e6d8a939753077792b0554', $cache->getValues());
        $this->assertArrayHasKey('register_album', $cache->getValues());

        $service->invalidateCacheAlbum('douze', '7b52009b64fd0a2a49e6d8a939753077792b0554');
        $this->assertCount(3, $cache->getValues());
        $this->assertArrayNotHasKey('album_douze_7b52009b64fd0a2a49e6d8a939753077792b0554', $cache->getValues());
        $this->assertArrayHasKey('album_treize_7b52009b64fd0a2a49e6d8a939753077792b0554', $cache->getValues());
        $this->assertArrayHasKey('register_album', $cache->getValues());
        /** @var string[] $cacheValues */
        $cacheValues = $cache->getValues();
        $this->assertEquals(
            'a:1:{i:0;s:53:"album_treize_7b52009b64fd0a2a49e6d8a939753077792b0554";}',
            $cacheValues['register_album']
        );

        $service->invalidateCacheAlbum('treize', '7b52009b64fd0a2a49e6d8a939753077792b0554');
        $this->assertCount(2, $cache->getValues());
        $this->assertArrayNotHasKey('album_douze_7b52009b64fd0a2a49e6d8a939753077792b0554', $cache->getValues());
        $this->assertArrayNotHasKey('album_treize_7b52009b64fd0a2a49e6d8a939753077792b0554', $cache->getValues());
        $this->assertArrayHasKey('register_album', $cache->getValues());
        /** @var string[] $cacheValues */
        $cacheValues = $cache->getValues();
        $this->assertEquals('a:0:{}', $cacheValues['register_album']);

        $service->getPokedex('douze', '7b52009b64fd0a2a49e6d8a939753077792b0554');
        $service->getPokedex('treize', '7b52009b64fd0a2a49e6d8a939753077792b0554');
        $this->assertArrayHasKey('album_douze_7b52009b64fd0a2a49e6d8a939753077792b0554', $cache->getValues());
        $this->assertArrayHasKey('album_treize_7b52009b64fd0a2a49e6d8a939753077792b0554', $cache->getValues());
        $this->assertArrayHasKey('register_album', $cache->getValues());

        $service->invalidateCacheAlbums();
        $this->assertCount(1, $cache->getValues());
        $this->assertArrayNotHasKey('album_douze_7b52009b64fd0a2a49e6d8a939753077792b0554', $cache->getValues());
        $this->assertArrayNotHasKey('album_treize_7b52009b64fd0a2a49e6d8a939753077792b0554', $cache->getValues());
        $this->assertArrayNotHasKey('register_album', $cache->getValues());
    }
    public function testReportsCache(): void
    {
        $client = $this->createMock(HttpClientInterface::class);

        $response = $this->createMock(ResponseInterface::class);
        $response
            ->expects($this->exactly(2))
            ->method('getContent')
            ->willReturn('{}')
        ;

        $client
            ->expects($this->exactly(2))
            ->method('request')
            ->withConsecutive(
                ['GET', 'api/reports'],
                // Additionnal call to pollute requests and caches
                ['GET', 'api/catch_states'],
            )
            ->willReturn($response)
        ;

        $cache = new ArrayAdapter();

        $service = new ApiService($client, 'api', $cache, 'web', 'douze');

        $this->assertEmpty($cache->getValues());

        $service->getReports();
        $this->assertCount(1, $cache->getValues());
        $this->assertArrayHasKey('reports', $cache->getValues());

        $service->getReports();
        $service->getReports();
        $this->assertCount(1, $cache->getValues());
        $this->assertArrayHasKey('reports', $cache->getValues());

        $service->getCatchStates();
        $this->assertCount(2, $cache->getValues());
        $this->assertArrayHasKey('reports', $cache->getValues());

        $service->invalidateCacheReports();
        $this->assertCount(1, $cache->getValues());
        $this->assertArrayNotHasKey('reports', $cache->getValues());
    }
}
