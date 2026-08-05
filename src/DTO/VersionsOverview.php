<?php

declare(strict_types=1);

namespace App\DTO;

final class VersionsOverview
{
    public function __construct(
        public readonly ?string $web,
        public readonly ?string $back,
        public readonly ?string $api,
        public readonly ?string $resources,
    ) {}
}
