<?php

declare(strict_types=1);

namespace App\ResponseObject;

use Symfony\Component\Serializer\Attribute\SerializedName;

final class BannerPipelineStatus
{
    /**
     * Same verified php-code-coverage artifact as BannerPipelineStageStatus::__construct()
     * — see that constructor's docblock.
     *
     * @codeCoverageIgnore
     */
    public function __construct(
        #[SerializedName('correlation_id')]
        public readonly string $correlationId,
        #[SerializedName('workflow_a')]
        public readonly BannerPipelineStageStatus $workflowA,
        #[SerializedName('icon_pr')]
        public readonly BannerPipelineStageStatus $iconPr,
        #[SerializedName('workflow_b')]
        public readonly BannerPipelineStageStatus $workflowB,
        #[SerializedName('resources_pr')]
        public readonly BannerPipelineStageStatus $resourcesPr,
    ) {}
}
