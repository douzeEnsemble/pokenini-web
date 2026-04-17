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
            '/election/vote',
            [
                'body' => json_encode([
                    'dex_slug' => $electionVote->dexSlug,
                    'election_slug' => $electionVote->electionSlug,
                    'winners_slugs' => $electionVote->winnersSlugs,
                    'losers_slugs' => $electionVote->losersSlugs,
                ]),
            ]
        );
    }
}
