<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\CacheInvalidator;

use App\Service\CacheInvalidator\FormsCacheInvalidatorService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

class FormsCacheInvalidatorServiceTest extends TestCase
{
    public function testInvalidate(): void
    {
        $cache = new ArrayAdapter();
        $cache->get('douze', fn() => 'DouZe');
        $cache->get('forms_category', fn() => 'whatever');
        $cache->get('forms_regional', fn() => 'whatever');
        $cache->get('forms_special', fn() => 'whatever');
        $cache->get('forms_variant', fn() => 'whatever');

        $service = new FormsCacheInvalidatorService($cache);
        $service->invalidate();

        $values = $cache->getValues();
        $this->assertCount(1, $values);
        $this->assertArrayHasKey('douze', $values);
    }

    public function testInvalidateWithAMissingOne(): void
    {
        $cache = new ArrayAdapter();
        $cache->get('douze', fn() => 'DouZe');
        $cache->get('forms_category', fn() => 'whatever');
        $cache->get('forms_regional', fn() => 'whatever');
        $cache->get('forms_variant', fn() => 'whatever');

        $service = new FormsCacheInvalidatorService($cache);
        $service->invalidate();

        $values = $cache->getValues();
        $this->assertCount(1, $values);
        $this->assertArrayHasKey('douze', $values);
    }
}
