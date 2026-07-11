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
        #[SerializedName('round_count')]
        private readonly int $roundCount,
        #[SerializedName('total_round_count')]
        private readonly int $totalRoundCount,
    ) {}

    public function getCompletion(): ElectionReportCompletion
    {
        return $this->completion;
    }

    public function getDexTotalCount(): int
    {
        return $this->dexTotalCount;
    }

    public function getRoundCount(): int
    {
        return $this->roundCount;
    }

    public function getTotalRoundCount(): int
    {
        return $this->totalRoundCount;
    }
}
