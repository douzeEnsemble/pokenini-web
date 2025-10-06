<?php

namespace App\Service;

use App\DTO\ElectionVote;
use App\Service\Back\PostElectionVoteService;

class ElectionVoteService
{
    public function __construct(
        private readonly PostElectionVoteService $apiService,
    ) {}

    public function vote(ElectionVote $electionVote): void
    {
        $this->apiService->vote($electionVote);
    }
}
