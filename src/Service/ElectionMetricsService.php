<?php

declare(strict_types=1);

namespace App\Service;

use App\DTO\ElectionMetrics;
use App\Service\Back\GetElectionMetricsService;

class ElectionMetricsService
{
    public function __construct(
        private readonly GetElectionMetricsService $apiService,
        private readonly int $electionCandidateCount,
    ) {}

    public function getMetrics(string $dexSlug, string $electionSlug): ElectionMetrics
    {
        $data = $this->apiService->getMetrics($dexSlug, $electionSlug);

        return new ElectionMetrics($data, $this->electionCandidateCount);
    }
}
