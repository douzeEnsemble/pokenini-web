<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\CacheInvalidator;

use App\Service\CacheInvalidator\ReportsCacheInvalidatorService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

class ReportsCacheInvalidatorServiceTest extends TestCase
{
    public function testInvalidate(): void
    {
        $cache = new ArrayAdapter();
        $cache->get('douze', fn() => 'DouZe');
        $cache->get('reports', fn() => 'whatever');

        $service = new ReportsCacheInvalidatorService($cache);
        $service->invalidate();

        $values = $cache->getValues();
        $this->assertCount(1, $values);
        $this->assertArrayHasKey('douze', $values);
    }
}
