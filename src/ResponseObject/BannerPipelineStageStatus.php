<?php

declare(strict_types=1);

namespace App\ResponseObject;

final class BannerPipelineStageStatus
{
    /**
     * Same verified php-code-coverage false-negative already documented on
     * the sibling ImagePipelineStageStatus (identical property-promotion
     * shape) — see that class's docblock for how it was verified.
     *
     * @codeCoverageIgnore
     */
    public function __construct(
        public readonly string $state,
        public readonly ?string $url,
    ) {}
}
