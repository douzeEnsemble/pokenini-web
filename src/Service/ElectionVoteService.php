<?php

namespace App\Service;

use App\DTO\ElectionVote;
use App\Security\UserTokenService;
use App\Service\Back\PostElectionVoteService;

class ElectionVoteService
{
    public function __construct(
        private readonly UserTokenService $userTokenService,
        private readonly PostElectionVoteService $apiService,
    ) {}

    public function vote(ElectionVote $electionVote): void
    {
        $trainerId = $this->userTokenService->getLoggedUserToken();

        $this->apiService->vote($trainerId, $electionVote);
    }
}
