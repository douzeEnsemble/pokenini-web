<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\CacheInvalidator;

use App\Service\CacheInvalidator\FormsCacheInvalidatorService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Adapter\TagAwareAdapter;

/**
 * @internal
 */
#[CoversClass(FormsCacheInvalidatorService::class)]
class FormsCacheInvalidatorServiceTest extends TestCase
{
    public function testInvalidate(): void
    {
        $cachePool = new ArrayAdapter();
        $cache = new TagAwareAdapter($cachePool, new ArrayAdapter());

        $cache->get('douze', fn () => 'DouZe');
        $cache->get('forms_category', fn () => 'whatever');
        $cache->get('forms_regional', fn () => 'whatever');
        $cache->get('forms_special', fn () => 'whatever');
        $cache->get('forms_variant', fn () => 'whatever');

        $service = new FormsCacheInvalidatorService($cache);
        $service->invalidate();

        $this->assertTrue($cache->hasItem('douze'));
        $this->assertFalse($cache->hasItem('forms_category'));
        $this->assertFalse($cache->hasItem('forms_regional'));
        $this->assertFalse($cache->hasItem('forms_special'));
        $this->assertFalse($cache->hasItem('forms_variant'));
    }

    public function testInvalidateWithAMissingOne(): void
    {
        $cachePool = new ArrayAdapter();
        $cache = new TagAwareAdapter($cachePool, new ArrayAdapter());

        $cache->get('douze', fn () => 'DouZe');
        $cache->get('forms_category', fn () => 'whatever');
        $cache->get('forms_regional', fn () => 'whatever');
        $cache->get('forms_variant', fn () => 'whatever');

        $service = new FormsCacheInvalidatorService($cache);
        $service->invalidate();

        $this->assertTrue($cache->hasItem('douze'));
        $this->assertFalse($cache->hasItem('forms_category'));
        $this->assertFalse($cache->hasItem('forms_regional'));
        $this->assertFalse($cache->hasItem('forms_special'));
        $this->assertFalse($cache->hasItem('forms_variant'));
    }
}
