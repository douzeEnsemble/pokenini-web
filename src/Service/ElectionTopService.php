<?php

namespace App\Service;

use App\Service\Back\GetElectionTopService;

class ElectionTopService
{
    public function __construct(
        private readonly GetElectionTopService $apiService,
        private readonly int $electionTopCount,
    ) {}

    /**
     * @return string[][]
     */
    public function getTop(string $dexSlug, string $electionSlug): array
    {
        return $this->apiService->getTop($dexSlug, $electionSlug, $this->electionTopCount);
    }
}
