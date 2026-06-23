<?php

declare(strict_types=1);

namespace App\DTO;

final class ElectionMetricsCounts
{
    public function __construct(
        public readonly int $sum,
        public readonly int $max,
    ) {}

    public function getSum(): int
    {
        return $this->sum;
    }

    public function getMax(): int
    {
        return $this->max;
    }
}
