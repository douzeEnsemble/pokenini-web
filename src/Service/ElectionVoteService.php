<?php

namespace App\Service;

use App\DTO\ElectionVote;
use App\Security\UserTokenService;
use App\Service\Api\ElectionVoteApiService;

class ElectionVoteService
{
    public function __construct(
        private readonly UserTokenService $userTokenService,
        private readonly ElectionVoteApiService $apiService,
    ) {}

    public function vote(ElectionVote $electionVote): void
    {
        $trainerId = $this->userTokenService->getLoggedUserToken();

        $this->apiService->vote($trainerId, $electionVote);
    }
}
