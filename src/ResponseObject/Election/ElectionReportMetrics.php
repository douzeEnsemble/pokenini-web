<?php

declare(strict_types=1);

namespace App\ResponseObject\Election;

use Symfony\Component\Serializer\Attribute\SerializedName;

final class ElectionReportMetrics
{
    public function __construct(
        #[SerializedName('completion')]
        private readonly ElectionReportCompletion $completion,
        #[SerializedName('dex_total_count')]
        private readonly int $dexTotalCount,
    ) {}

    public function getCompletion(): ElectionReportCompletion
    {
        return $this->completion;
    }

    public function getDexTotalCount(): int
    {
        return $this->dexTotalCount;
    }
}
