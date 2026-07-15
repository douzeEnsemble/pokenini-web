<?php

declare(strict_types=1);

namespace App\ResponseObject;

final class ImagePipelineStageStatus
{
    public function __construct(
        public readonly string $state,
        public readonly ?string $url,
    ) {}
}
