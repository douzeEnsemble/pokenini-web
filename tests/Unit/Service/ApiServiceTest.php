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
    public function testGetDex(): void
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
            $service->getDex('7b52009b64fd0a2a49e6d8a939753077792b0554'),
        );
    }

    public function testGetDexWithUnreleased(): void
    {
        $service = $this->getService('dex/7b52009b64fd0a2a49e6d8a939753077792b0554/list?include_unreleased_dex=1');

        $this->assertEquals(
            [
                'trucs' => [
                    'bidule',
                    'machin',
                    'chose',
                ],
                'url' => 'dex/7b52009b64fd0a2a49e6d8a939753077792b0554/list?include_unreleased_dex=1',
            ],
            $service->getDexWithUnreleased('7b52009b64fd0a2a49e6d8a939753077792b0554'),
        );
    }

    public function testGetPokedex(): void
    {
        $service = $this->getService('album/7b52009b64fd0a2a49e6d8a939753077792b0554/douze');

        $this->assertEquals(
            [
                'trucs' => [
                    'bidule',
                    'machin',
                    'chose',
                ],
                'url' => 'album/7b52009b64fd0a2a49e6d8a939753077792b0554/douze',
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

    public function testGetTypes(): void
    {
        $service = $this->getService('types');

        $this->assertEquals(
            [
                'trucs' => [
                    'bidule',
                    'machin',
                    'chose',
                ],
                'url' => 'types',
            ],
            $service->getTypes(),
        );
    }

    public function testGetFormsCategory(): void
    {
        $service = $this->getService('forms/category');

        $this->assertEquals(
            [
                'trucs' => [
                    'bidule',
                    'machin',
                    'chose',
                ],
                'url' => 'forms/category',
            ],
            $service->getFormsCategory(),
        );
    }

    public function testGetFormsRegional(): void
    {
        $service = $this->getService('forms/regional');

        $this->assertEquals(
            [
                'trucs' => [
                    'bidule',
                    'machin',
                    'chose',
                ],
                'url' => 'forms/regional',
            ],
            $service->getFormsRegional(),
        );
    }

    public function testGetFormsSpecial(): void
    {
        $service = $this->getService('forms/special');

        $this->assertEquals(
            [
                'trucs' => [
                    'bidule',
                    'machin',
                    'chose',
                ],
                'url' => 'forms/special',
            ],
            $service->getFormsSpecial(),
        );
    }

    public function testGetFormsVariant(): void
    {
        $service = $this->getService('forms/variant');

        $this->assertEquals(
            [
                'trucs' => [
                    'bidule',
                    'machin',
                    'chose',
                ],
                'url' => 'forms/variant',
            ],
            $service->getFormsVariant(),
        );
    }

    public function testModifyAlbum(): void
    {
        $client = $this->createMock(HttpClientInterface::class);

        $client
            ->expects($this->once())
            ->method('request')
            ->with(
                'PATCH',
                'api/album/7b52009b64fd0a2a49e6d8a939753077792b0554/a/b',
                [
                    'body' => 'c',
                    'auth_basic' => [
                        'web',
                        'douze',
                    ],
                ]
            )
        ;

        $cache = new ArrayAdapter();

        $service = new ApiService($client, 'api', $cache, 'web', 'douze');

        $service->modifyAlbum('PATCH', 'a', 'b', 'c', '7b52009b64fd0a2a49e6d8a939753077792b0554');
    }

    public function testModifyAlbumPut(): void
    {
        $client = $this->createMock(HttpClientInterface::class);

        $client
            ->expects($this->once())
            ->method('request')
            ->with(
                'PUT',
                'api/album/7b52009b64fd0a2a49e6d8a939753077792b0554/a/b',
                [
                    'body' => 'c',
                    'auth_basic' => [
                        'web',
                        'douze',
                    ],
                ]
            )
        ;

        $cache = new ArrayAdapter();

        $service = new ApiService($client, 'api', $cache, 'web', 'douze');

        $service->modifyAlbum('PUT', 'a', 'b', 'c', '7b52009b64fd0a2a49e6d8a939753077792b0554');
    }

    public function testModifyAlbumPost(): void
    {
        $client = $this->createMock(HttpClientInterface::class);

        $client
            ->expects($this->never())
            ->method('request')
        ;

        $cache = new ArrayAdapter();

        $service = new ApiService($client, 'api', $cache, 'web', 'douze');

        $this->expectException(\InvalidArgumentException::class);
        $service->modifyAlbum('POST', 'a', 'b', 'c', '7b52009b64fd0a2a49e6d8a939753077792b0554');
    }

    public function testModifyDex(): void
    {
        $client = $this->createMock(HttpClientInterface::class);

        $client
            ->expects($this->exactly(1))
            ->method('request')
            ->with(
                'PUT',
                'api/dex/7b52009b64fd0a2a49e6d8a939753077792b0554/a',
                [
                    'body' => '{"b": "c"}',
                    'auth_basic' => [
                        'web',
                        'douze',
                    ],
                ]
            )
        ;

        $cache = new ArrayAdapter();

        $service = new ApiService($client, 'api', $cache, 'web', 'douze');

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

        return new ApiService($client, 'api', new ArrayAdapter(), 'web', 'douze');
    }
}
