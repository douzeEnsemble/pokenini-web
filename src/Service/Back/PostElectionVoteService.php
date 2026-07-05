<?php

declare(strict_types=1);

namespace App\Service\Back;

use App\DTO\ElectionVote;

class PostElectionVoteService extends AbstractBackService
{
    public function vote(
        ElectionVote $electionVote,
    ): void {
        $this->requestContent(
            'POST',
            "/election/{$electionVote->dexSlug}".($electionVote->electionSlug ? "/{$electionVote->electionSlug}" : ''),
            [
                'json' => [
                    'winners_slugs' => $electionVote->winnersSlugs,
                    'losers_slugs' => $electionVote->losersSlugs,
                ],
            ]
        );
    }
}
