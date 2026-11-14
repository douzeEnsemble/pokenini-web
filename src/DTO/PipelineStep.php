<?php

declare(strict_types=1);

namespace App\DTO;

final readonly class PipelineStep
{
    /**
     * @param list<string> $dependsOnTitleKeys translation keys of the titles of the steps this one comes after
     */
    public function __construct(
        public string $type,
        public string $name,
        public string $page,
        public string $icon,
        public array $dependsOnTitleKeys = [],
    ) {}
}
