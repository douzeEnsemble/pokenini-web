<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Api;

use App\Service\Api\GetDexService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class GetDexServiceTest extends TestCase
{
    private ArrayAdapter $cache;

    public function testGet(): void
    {
        $expectedResult = [
            [
                'is_shiny' => false,
                'is_private' => false,
                'is_on_home' => false,
                'is_display_form' => false,
                'display_template' => 'list-7',
                'name' => 'Home Pokemon Go',
                'french_name' => 'Home Pokemon Go',
                'slug' => 'homepokemongo',
                'original_slug' => 'homepokemongo',
                'is_released' => true,
            ],
            [
                'is_shiny' => false,
                'is_private' => true,
                'is_on_home' => false,
                'is_display_form' => true,
                'display_template' => 'list-3',
                'name' => 'Alpha',
                'french_name' => 'Baron',
                'slug' => 'alpha',
                'original_slug' => 'alpha',
                'is_released' => true,
            ],
            [
                'is_shiny' => false,
                'is_private' => true,
                'is_on_home' => false,
                'is_display_form' => true,
                'display_template' => 'list-3',
                'name' => 'Mega',
                'french_name' => 'Méga',
                'slug' => 'mega',
                'original_slug' => 'mega',
                'is_released' => true,
            ],
        ];

        $this->assertEquals(
            $expectedResult,
            $this->getService('123')->get('123'),
        );

        /** @var string $value */
        $value = $this->cache->getItem('dex_123')->get();

        $this->assertJsonStringEqualsJsonString(
            (string) json_encode($expectedResult),
            $value,
        );
    }
    public function testGetWithUnreleased(): void
    {
        $expectedResult = [
            [
                'is_shiny' => false,
                'is_private' => true,
                'is_on_home' => false,
                'is_display_form' => true,
                'display_template' => 'box',
                'region' => [
                  'name' => 'Kanto',
                  'french_name' => 'Kanto',
                  'slug' => 'kanto'
                ],
                'name' => 'Red, Green, Blue, Yellow',
                'french_name' => 'Rouge, Vert, Bleu, Jaune',
                'slug' => 'redgreenblueyellow',
                'original_slug' => 'redgreenblueyellow',
                'is_released' => false,
            ],
            [
                'is_shiny' => false,
                'is_private' => false,
                'is_on_home' => false,
                'is_display_form' => false,
                'display_template' => 'list-7',
                'name' => 'Home Pokemon Go',
                'french_name' => 'Home Pokemon Go',
                'slug' => 'homepokemongo',
                'original_slug' => 'homepokemongo',
                'is_released' => true,
            ],
            [
                'is_shiny' => false,
                'is_private' => true,
                'is_on_home' => false,
                'is_display_form' => true,
                'display_template' => 'list-3',
                'name' => 'Alpha',
                'french_name' => 'Baron',
                'slug' => 'alpha',
                'original_slug' => 'alpha',
                'is_released' => true,
            ],
            [
                'is_shiny' => false,
                'is_private' => true,
                'is_on_home' => false,
                'is_display_form' => true,
                'display_template' => 'list-3',
                'name' => 'Mega',
                'french_name' => 'Méga',
                'slug' => 'mega',
                'original_slug' => 'mega',
                'is_released' => true,
            ],
        ];

        $this->assertEquals(
            $expectedResult,
            $this->getServiceWithUnreleased('123')->getWithUnreleased('123'),
        );

        /** @var string $value */
        $value = $this->cache->getItem('dex_123include_unreleased_dex=1')->get();

        $this->assertJsonStringEqualsJsonString(
            (string) json_encode($expectedResult),
            $value,
        );

        $this->assertEquals(
            [
                'dex_123include_unreleased_dex=1',
            ],
            $this->cache->getItem('register_dex')->get(),
        );
    }

    private function getService(string $trainerId): GetDexService
    {
        $json = (string) file_get_contents(
            "/var/www/html/tests/resources/unit/service/api/dex_{$trainerId}.json"
        );

        return $this->getMockService(
            $json,
            "dex/$trainerId/list",
        );
    }

    private function getServiceWithUnreleased(string $trainerId): GetDexService
    {
        $json = (string) file_get_contents(
            "/var/www/html/tests/resources/unit/service/api/dex_{$trainerId}_unreleased.json"
        );

        return $this->getMockService(
            $json,
            "dex/$trainerId/list?include_unreleased_dex=1",
        );
    }

    private function getMockService(
        string $json,
        string $endpoint,
    ): GetDexService {

        $client = $this->createMock(HttpClientInterface::class);

        $response = $this->createMock(ResponseInterface::class);
        $response
            ->expects($this->once())
            ->method('getContent')
            ->willReturn($json)
        ;

        $client
            ->expects($this->once())
            ->method('request')
            ->with(
                'GET',
                "https://api.domain/$endpoint",
                [
                    'headers' => [
                        'accept' => 'application/json',
                    ],
                    'auth_basic' => [
                        'web',
                        'douze',
                    ],
                ],
            )
            ->willReturn($response)
        ;

        $this->cache = new ArrayAdapter();

        return new GetDexService(
            $client,
            'https://api.domain',
            $this->cache,
            'web',
            'douze',
        );
    }
}
