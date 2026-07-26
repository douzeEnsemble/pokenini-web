<?php

declare(strict_types=1);

namespace App\ResponseObject;

use Symfony\Component\Serializer\Attribute\SerializedName;

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
        #[SerializedName('correlation_id')]
        public readonly string $correlationId,
        #[SerializedName('workflow_a')]
        public readonly ImagePipelineStageStatus $workflowA,
        #[SerializedName('icon_pr')]
        public readonly ImagePipelineStageStatus $iconPr,
        #[SerializedName('workflow_b')]
        public readonly ImagePipelineStageStatus $workflowB,
        #[SerializedName('resources_pr')]
        public readonly ImagePipelineStageStatus $resourcesPr,
    ) {}
}
