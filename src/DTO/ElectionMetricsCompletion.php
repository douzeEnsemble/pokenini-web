<?php

declare(strict_types=1);

namespace App\DTO;

final class ElectionMetricsCompletion
{
    public function __construct(
        public readonly int $underMaxCount,
        public readonly int $atMaxCount,
    ) {}

    public function getUnderMaxCount(): int
    {
        return $this->underMaxCount;
    }

    public function getAtMaxCount(): int
    {
        return $this->atMaxCount;
    }
}
