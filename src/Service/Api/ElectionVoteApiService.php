<?php

declare(strict_types=1);

namespace App\Service\Api;

use App\DTO\ElectionVote;
use App\Utils\JsonDecoder;

class ElectionVoteApiService extends AbstractApiService
{
    /**
     * @return int[]|int[][]|string[]|string[][]
     */
    public function vote(
        string $trainerId,
        ElectionVote $electionVote,
    ): array {
        /** @var string $json */
        $json = $this->requestContent(
            'POST',
            '/election/vote',
            [
                'body' => json_encode([
                    'trainer_external_id' => $trainerId,
                    'dex_slug' => $electionVote->dexSlug,
                    'election_slug' => $electionVote->electionSlug,
                    'winners_slugs' => $electionVote->winnersSlugs,
                    'losers_slugs' => $electionVote->losersSlugs,
                ]),
            ]
        );

        /** @var int[]|int[][]|string[]|string[][] */
        return JsonDecoder::decode($json);
    }
}
