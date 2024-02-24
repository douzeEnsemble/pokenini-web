<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\CacheInvalidator;

use App\Service\CacheInvalidator\AlbumCacheInvalidatorService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

class AlbumCacheInvalidatorServiceTest extends TestCase
{
    public function testInvalidate(): void
    {
        $cache = new ArrayAdapter();
        $cache->get('douze', fn() => 'DouZe');
        $cache->get('album_home_123', fn() => 'whatever');
        $cache->get('album_home_456', fn() => 'whatever');
        $cache->get('register_album', fn() => ['album_home_123', 'album_home_456']);

        $service = new AlbumCacheInvalidatorService($cache);
        $service->invalidate('home', '123');

        $values = $cache->getValues();
        $this->assertCount(3, $values);
        $this->assertArrayHasKey('douze', $values);
        $this->assertArrayHasKey('album_home_456', $values);
        $this->assertArrayHasKey('register_album', $values);
        /** @var string[] $register */
        $register = $cache->getItem('register_album')->get();
        $this->assertCount(1, $register);
    }
}
