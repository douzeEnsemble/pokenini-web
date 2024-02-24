<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\CacheInvalidator;

use App\Service\CacheInvalidator\DexCacheInvalidatorService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

class DexCacheInvalidatorServiceTest extends TestCase
{
    public function testInvalidate(): void
    {
        $cache = new ArrayAdapter();
        $cache->get('douze', fn() => 'DouZe');
        $cache->get('dex_123', fn() => 'whatever');
        $cache->get('dex_456', fn() => 'whatever');
        $cache->get('dex_789', fn() => 'whatever');
        $cache->get('register_dex', fn() => ['dex_123', 'dex_456']);

        $service = new DexCacheInvalidatorService($cache);
        $service->invalidate();

        $values = $cache->getValues();
        $this->assertCount(2, $values);
        $this->assertArrayHasKey('douze', $values);
        $this->assertArrayHasKey('dex_789', $values);
    }

    public function testInvalidateByTrainerId(): void
    {
        $cache = new ArrayAdapter();
        $cache->get('douze', fn() => 'DouZe');
        $cache->get('dex_123', fn() => 'whatever');
        $cache->get('dex_456', fn() => 'whatever');
        $cache->get('dex_789', fn() => 'whatever');
        $cache->get('register_dex', fn() => ['dex_123', 'dex_456']);

        $service = new DexCacheInvalidatorService($cache);
        $service->invalidateByTrainerId('123');

        $values = $cache->getValues();
        $this->assertCount(4, $values);
        $this->assertArrayHasKey('douze', $values);
        $this->assertArrayHasKey('dex_456', $values);
        $this->assertArrayHasKey('dex_789', $values);
        /** @var string[] $register */
        $register = $cache->getItem('register_dex')->get();
        $this->assertCount(1, $register);
    }
}
