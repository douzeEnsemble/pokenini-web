<?php

namespace App\Service;

use App\Security\UserTokenService;
use App\Service\Api\ElectionTopApiService;

class ElectionTopService
{
    public function __construct(
        private readonly UserTokenService $userTokenService,
        private readonly ElectionTopApiService $apiService,
        private readonly int $topCount,
    ) {}

    /**
     * @return string[][]
     */
    public function getTop(string $dexSlug, string $electionSlug): array
    {
        $trainerId = $this->userTokenService->getLoggedUserToken();

        return $this->apiService->getTop($trainerId, $dexSlug, $electionSlug, $this->topCount);
    }
}
