<?php

declare(strict_types=1);

namespace App\Tests\Functional\Service;

use App\Service\ApiService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class ApiServiceTest extends KernelTestCase
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
            ->willReturn($response)
        ;

        $cache = new ArrayAdapter();

        $service = new ApiService($client, 'api', $cache);

        $service->getCatchStates();
        $this->assertCount(1, $cache->getValues());

        $service->getCatchStates();
        $service->getCatchStates();
        $this->assertCount(1, $cache->getValues());

        $service->getDexes();
        $this->assertCount(2, $cache->getValues());

        $service->getPokedex('douze');
        $this->assertCount(3, $cache->getValues());

        $service->getPokedex('treize');
        $this->assertCount(4, $cache->getValues());

        $service->getPokedex('treize');
        $service->getPokedex('treize');
        $service->getPokedex('douze');
        $this->assertCount(4, $cache->getValues());
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
