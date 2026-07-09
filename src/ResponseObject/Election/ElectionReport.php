<?php

declare(strict_types=1);

namespace App\ResponseObject\Election;

use Symfony\Component\Serializer\Attribute\SerializedName;

final class ElectionReport
{
    public function __construct(
        #[SerializedName('metrics')]
        private readonly ElectionReportMetrics $metrics,
    ) {}

    public function getMetrics(): ElectionReportMetrics
    {
        return $this->metrics;
    }
}
