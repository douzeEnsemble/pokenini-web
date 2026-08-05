<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\ResponseObject\Versions;
use App\Service\AppVersionService;
use App\Service\Back\GetVersionsService;
use App\Service\GetResourcesVersionService;
use App\Service\VersionsOverviewService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(VersionsOverviewService::class)]
final class VersionsOverviewServiceTest extends TestCase
{
    public function testGetCombinesAllFourVersions(): void
    {
        $appVersionService = $this->createMock(AppVersionService::class);
        $appVersionService->method('getVersion')->willReturn('1.2.12');

        $getVersionsService = $this->createMock(GetVersionsService::class);
        $getVersionsService->method('get')->willReturn(new Versions('1.9.9', '1.9.8'));

        $getResourcesVersionService = $this->createMock(GetResourcesVersionService::class);
        $getResourcesVersionService->method('get')->willReturn('1.9.7');

        $service = new VersionsOverviewService($appVersionService, $getVersionsService, $getResourcesVersionService);

        $overview = $service->get();

        $this->assertSame('1.2.12', $overview->web);
        $this->assertSame('1.9.9', $overview->back);
        $this->assertSame('1.9.8', $overview->api);
        $this->assertSame('1.9.7', $overview->resources);
    }

    public function testGetHandlesUnavailableBricks(): void
    {
        $appVersionService = $this->createMock(AppVersionService::class);
        $appVersionService->method('getVersion')->willReturn('1.2.12');

        $getVersionsService = $this->createMock(GetVersionsService::class);
        $getVersionsService->method('get')->willReturn(new Versions(null, null));

        $getResourcesVersionService = $this->createMock(GetResourcesVersionService::class);
        $getResourcesVersionService->method('get')->willReturn(null);

        $service = new VersionsOverviewService($appVersionService, $getVersionsService, $getResourcesVersionService);

        $overview = $service->get();

        $this->assertSame('1.2.12', $overview->web);
        $this->assertNull($overview->back);
        $this->assertNull($overview->api);
        $this->assertNull($overview->resources);
    }
}
