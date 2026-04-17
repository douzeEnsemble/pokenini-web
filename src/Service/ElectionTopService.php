<?php

declare(strict_types=1);

namespace App\Service;

use App\DTO\ElectionTop;
use App\Service\Back\GetElectionTopService;

class ElectionTopService
{
    public function __construct(
        private readonly GetElectionTopService $apiService,
        private readonly int $electionTopCount,
    ) {}

    public function getTop(string $dexSlug, string $electionSlug): ElectionTop
    {
        return $this->apiService->getTop($dexSlug, $electionSlug, $this->electionTopCount);
    }
}
