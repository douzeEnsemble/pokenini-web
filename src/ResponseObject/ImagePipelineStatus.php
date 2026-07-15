<?php

declare(strict_types=1);

namespace App\ResponseObject;

final class ImagePipelineStatus
{
    /**
     * Same verified php-code-coverage artifact as
     * ImagePipelineStageStatus::__construct() - see that constructor's
     * docblock for how it was verified.
     *
     * @codeCoverageIgnore
     */
    public function __construct(
        public readonly string $correlationId,
        public readonly ImagePipelineStageStatus $workflowA,
        public readonly ImagePipelineStageStatus $iconPr,
        public readonly ImagePipelineStageStatus $workflowB,
        public readonly ImagePipelineStageStatus $resourcesPr,
    ) {}
}
