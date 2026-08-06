<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\ResponseObject\BrickVersion;
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
        $webUpdatedAt = new \DateTimeImmutable('2026-08-05T09:12:00+00:00');
        $backUpdatedAt = new \DateTimeImmutable('2026-08-04T21:47:00+00:00');
        $apiUpdatedAt = new \DateTimeImmutable('2026-08-03T15:03:00+00:00');
        $resourcesUpdatedAt = new \DateTimeImmutable('2026-08-02T10:00:00+00:00');

        $appVersionService = $this->createStub(AppVersionService::class);
        $appVersionService->method('getVersion')->willReturn('1.2.12');
        $appVersionService->method('getUpdatedAt')->willReturn($webUpdatedAt);

        $getVersionsService = $this->createStub(GetVersionsService::class);
        $getVersionsService->method('get')->willReturn(new Versions(
            new BrickVersion('1.9.9', $backUpdatedAt),
            new BrickVersion('1.9.8', $apiUpdatedAt),
        ));

        $getResourcesVersionService = $this->createStub(GetResourcesVersionService::class);
        $getResourcesVersionService->method('get')->willReturn(new BrickVersion('1.9.7', $resourcesUpdatedAt));

        $service = new VersionsOverviewService($appVersionService, $getVersionsService, $getResourcesVersionService);

        $overview = $service->get();

        $this->assertSame('1.2.12', $overview->web->version);
        $this->assertSame($webUpdatedAt, $overview->web->updatedAt);
        $this->assertSame('1.9.9', $overview->back->version);
        $this->assertSame($backUpdatedAt, $overview->back->updatedAt);
        $this->assertSame('1.9.8', $overview->api->version);
        $this->assertSame($apiUpdatedAt, $overview->api->updatedAt);
        $this->assertSame('1.9.7', $overview->resources->version);
        $this->assertSame($resourcesUpdatedAt, $overview->resources->updatedAt);
    }

    public function testGetHandlesUnavailableBricks(): void
    {
        $appVersionService = $this->createStub(AppVersionService::class);
        $appVersionService->method('getVersion')->willReturn('1.2.12');
        $appVersionService->method('getUpdatedAt')->willReturn(null);

        $getVersionsService = $this->createStub(GetVersionsService::class);
        $getVersionsService->method('get')->willReturn(new Versions(
            new BrickVersion(null, null),
            new BrickVersion(null, null),
        ));

        $getResourcesVersionService = $this->createStub(GetResourcesVersionService::class);
        $getResourcesVersionService->method('get')->willReturn(new BrickVersion(null, null));

        $service = new VersionsOverviewService($appVersionService, $getVersionsService, $getResourcesVersionService);

        $overview = $service->get();

        $this->assertSame('1.2.12', $overview->web->version);
        $this->assertNull($overview->web->updatedAt);
        $this->assertNull($overview->back->version);
        $this->assertNull($overview->api->version);
        $this->assertNull($overview->resources->version);
    }
}
