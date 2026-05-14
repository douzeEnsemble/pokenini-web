<?php

declare(strict_types=1);

namespace App\Service;

use App\DTO\ElectionVote;
use App\Exception\ModifyFailedException;
use App\Service\Back\PostElectionVoteService;
use Symfony\Contracts\HttpClient\Exception\HttpExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class ElectionVoteService
{
    public function __construct(
        private readonly PostElectionVoteService $apiService,
    ) {}

    /**
     * @throws ModifyFailedException
     */
    public function vote(ElectionVote $electionVote): void
    {
        try {
            $this->apiService->vote($electionVote);
        } catch (HttpExceptionInterface|TransportExceptionInterface $e) {
            throw new ModifyFailedException();
        }
    }
}
