<?php

declare(strict_types=1);

namespace App\DTO;

use App\ResponseObject\BrickVersion;

final class VersionsOverview
{
    public function __construct(
        public readonly BrickVersion $web,
        public readonly BrickVersion $back,
        public readonly BrickVersion $api,
        public readonly BrickVersion $resources,
    ) {}
}
