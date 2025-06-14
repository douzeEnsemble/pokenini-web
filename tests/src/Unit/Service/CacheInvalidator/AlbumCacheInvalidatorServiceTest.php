<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\CacheInvalidator;

use App\Service\CacheInvalidator\AlbumCacheInvalidatorService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Adapter\TagAwareAdapter;

/**
 * @internal
 */
#[CoversClass(AlbumCacheInvalidatorService::class)]
class AlbumCacheInvalidatorServiceTest extends TestCase
{
    public function testInvalidate(): void
    {
        $cachePool = new ArrayAdapter();
        $cache = new TagAwareAdapter($cachePool, new ArrayAdapter());

        $cache->get('douze', fn () => 'DouZe');
        $cache->get('album_home_123', fn () => 'whatever');
        $cache->get('album_home_456', fn () => 'whatever');

        $service = new AlbumCacheInvalidatorService($cache);
        $service->invalidate('unknown', '123');
        $service->invalidate('home', '123');

        $this->assertTrue($cache->hasItem('douze'));
        $this->assertTrue($cache->hasItem('album_home_456'));
        $this->assertFalse($cache->hasItem('album_home_123'));
    }

    public function testInvalidateMock(): void
    {
        $cache = $this->createMock(TagAwareAdapter::class);
        $cache
            ->expects($this->once())
            ->method('delete')
            ->with('album_unknown_123')
        ;
        $cache
            ->expects($this->once())
            ->method('invalidateTags')
            ->with(['trainer#123'])
        ;

        $service = new AlbumCacheInvalidatorService($cache);
        $service->invalidate('unknown', '123');
    }
}
