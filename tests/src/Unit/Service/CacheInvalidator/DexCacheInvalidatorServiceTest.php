<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\CacheInvalidator;

use App\Service\CacheInvalidator\DexCacheInvalidatorService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Adapter\TagAwareAdapter;
use Symfony\Contracts\Cache\ItemInterface;

/**
 * @internal
 */
#[CoversClass(DexCacheInvalidatorService::class)]
class DexCacheInvalidatorServiceTest extends TestCase
{
    public function testInvalidate(): void
    {
        $cachePool = new ArrayAdapter();
        $cache = new TagAwareAdapter($cachePool, new ArrayAdapter());

        $cache->get('douze', fn () => 'DouZe');
        $cache->get('dex_123', function (ItemInterface $item) {
            $item->tag([
                'dex',
                'trainer#123',
            ]);

            return 'whatever';
        });
        $cache->get('dex_456', function (ItemInterface $item) {
            $item->tag([
                'dex-wrong-tag',
                'trainer#456',
            ]);

            return 'whatever';
        });
        $cache->get('dex_789', function (ItemInterface $item) {
            $item->tag([
                'dex',
                'trainer#789',
            ]);

            return 'whatever';
        });

        $service = new DexCacheInvalidatorService($cache);
        $service->invalidate();

        $this->assertTrue($cache->hasItem('douze'));
        $this->assertTrue($cache->hasItem('dex_456'));
        $this->assertFalse($cache->hasItem('dex_123'));
        $this->assertFalse($cache->hasItem('dex_789'));
    }

    public function testInvalidateMock(): void
    {
        $cache = $this->createMock(TagAwareAdapter::class);
        $cache
            ->expects($this->once())
            ->method('invalidateTags')
            ->with(['dex'])
        ;

        $service = new DexCacheInvalidatorService($cache);
        $service->invalidate();
    }

    public function testInvalidateByTrainerId(): void
    {
        $cachePool = new ArrayAdapter();
        $cache = new TagAwareAdapter($cachePool, new ArrayAdapter());

        $cache->get('douze', fn () => 'DouZe');
        $cache->get('dex_123', function (ItemInterface $item) {
            $item->tag([
                'dex',
                'trainer#123',
            ]);

            return 'whatever';
        });
        $cache->get('dex_456', function (ItemInterface $item) {
            $item->tag([
                'dex',
                'trainer#456',
            ]);

            return 'whatever';
        });
        $cache->get('dex_789', function (ItemInterface $item) {
            $item->tag([
                'dex',
                'trainer#789',
            ]);

            return 'whatever';
        });

        $service = new DexCacheInvalidatorService($cache);
        $service->invalidateByTrainerId('unknown');
        $service->invalidateByTrainerId('123');

        $this->assertTrue($cache->hasItem('douze'));
        $this->assertTrue($cache->hasItem('dex_456'));
        $this->assertTrue($cache->hasItem('dex_789'));
        $this->assertFalse($cache->hasItem('dex_123'));
    }

    public function testInvalidateByTrainerIdWithHomeDex(): void
    {
        $cachePool = new ArrayAdapter();
        $cache = new TagAwareAdapter($cachePool, new ArrayAdapter());

        $cache->get('douze', fn () => 'DouZe');
        $cache->get('dex_123', function (ItemInterface $item) {
            $item->tag([
                'dex',
                'trainer#123',
            ]);

            return 'whatever';
        });
        $cache->get('dex_123#includeprivatedex', function (ItemInterface $item) {
            $item->tag([
                'dex',
                'trainer#123',
            ]);

            return 'whatever';
        });
        $cache->get('dex_456', function (ItemInterface $item) {
            $item->tag([
                'dex',
                'trainer#456',
            ]);

            return 'whatever';
        });
        $cache->get('dex_789', function (ItemInterface $item) {
            $item->tag([
                'dex',
                'trainer#789',
            ]);

            return 'whatever';
        });

        $service = new DexCacheInvalidatorService($cache);
        $service->invalidateByTrainerId('unknown');
        $service->invalidateByTrainerId('123');

        $this->assertTrue($cache->hasItem('douze'));
        $this->assertTrue($cache->hasItem('dex_456'));
        $this->assertTrue($cache->hasItem('dex_789'));
        $this->assertFalse($cache->hasItem('dex_123'));
        $this->assertFalse($cache->hasItem('dex_123#includeprivatedex'));
    }

    public function testInvalidateByTrainerIdMock(): void
    {
        $cache = $this->createMock(TagAwareAdapter::class);
        $cache
            ->expects($this->once())
            ->method('delete')
            ->with('dex_unknown')
        ;
        $cache
            ->expects($this->once())
            ->method('invalidateTags')
            ->with(['trainer#unknown'])
        ;

        $service = new DexCacheInvalidatorService($cache);
        $service->invalidateByTrainerId('unknown');
    }
}
