<?php

declare(strict_types=1);

namespace App\Service;

use App\DTO\VersionsOverview;
use App\ResponseObject\BrickVersion;
use App\Service\Back\GetVersionsService;

class VersionsOverviewService
{
    public function __construct(
        private readonly AppVersionService $appVersionService,
        private readonly GetVersionsService $getVersionsService,
        private readonly GetResourcesVersionService $getResourcesVersionService,
    ) {}

    public function get(): VersionsOverview
    {
        $versions = $this->getVersionsService->get();

        return new VersionsOverview(
            web: new BrickVersion($this->appVersionService->getVersion(), $this->appVersionService->getUpdatedAt()),
            back: $versions->back,
            api: $versions->api,
            resources: $this->getResourcesVersionService->get(),
        );
    }
}
