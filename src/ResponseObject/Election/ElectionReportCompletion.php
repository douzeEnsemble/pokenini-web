<?php

declare(strict_types=1);

namespace App\ResponseObject\Election;

use Symfony\Component\Serializer\Attribute\SerializedName;

final class ElectionReportCompletion
{
    public function __construct(
        #[SerializedName('at_max_count')]
        private readonly int $atMaxCount,
        #[SerializedName('under_max_count')]
        private readonly int $underMaxCount,
    ) {}

    public function getAtMaxCount(): int
    {
        return $this->atMaxCount;
    }

    public function getUnderMaxCount(): int
    {
        return $this->underMaxCount;
    }
}
