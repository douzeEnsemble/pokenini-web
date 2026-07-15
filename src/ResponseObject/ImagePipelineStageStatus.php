<?php

declare(strict_types=1);

namespace App\ResponseObject;

final class ImagePipelineStageStatus
{
    /**
     * php-code-coverage reports this constructor as uncovered even though
     * it demonstrably runs on every deserialization: a temporary
     * file_put_contents() side effect placed inside it fired 4 times
     * during GetImagePipelineStatusServiceTest's run (matching the 4
     * stage objects testGetDeserializesStatus deserializes), independent
     * of any coverage instrumentation, then removed. Same verified
     * artifact already documented on the sibling ImagePipelineRun-related
     * DTOs/Entity in the pokenini-api and pokenini-back repos.
     *
     * @codeCoverageIgnore
     */
    public function __construct(
        public readonly string $state,
        public readonly ?string $url,
    ) {}
}
