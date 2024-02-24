<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\CacheInvalidator;

use App\Service\CacheInvalidator\AlbumsCacheInvalidatorService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

class AlbumsCacheInvalidatorServiceTest extends TestCase
{
    public function testInvalidate(): void
    {
        $cache = new ArrayAdapter();
        $cache->get('douze', fn() => 'DouZe');
        $cache->get('album_home_123', fn() => 'whatever');
        $cache->get('album_home_456', fn() => 'whatever');
        $cache->get('register_album', fn() => ['album_home_123']);

        $service = new AlbumsCacheInvalidatorService($cache);
        $service->invalidate();

        $values = $cache->getValues();
        $this->assertCount(2, $values);
        $this->assertArrayHasKey('douze', $values);
        $this->assertArrayHasKey('album_home_456', $values);
    }
}
