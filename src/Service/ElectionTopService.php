<?php

namespace App\Service;

use App\Security\UserTokenService;
use App\Service\Back\GetElectionTopService;

class ElectionTopService
{
    public function __construct(
        private readonly UserTokenService $userTokenService,
        private readonly GetElectionTopService $apiService,
        private readonly int $electionTopCount,
    ) {}

    /**
     * @return string[][]
     */
    public function getTop(string $dexSlug, string $electionSlug): array
    {
        $trainerId = $this->userTokenService->getLoggedUserToken();

        return $this->apiService->getTop($trainerId, $dexSlug, $electionSlug, $this->electionTopCount);
    }
}
