<?php

declare(strict_types=1);

namespace App\Service\Back;

use App\Utils\JsonDecoder;

class GetElectionMetricsService extends AbstractBackService
{
    /**
     * @return float[]|int[]
     */
    public function getMetrics(
        string $dexSlug,
        string $electionSlug,
    ): array {
        /** @var string $json */
        $json = $this->requestContent(
            'GET',
            '/election/metrics',
            [
                'query' => [
                    'dex_slug' => $dexSlug,
                    'election_slug' => $electionSlug,
                ],
            ],
        );

        /** @var float[]|int[] */
        return JsonDecoder::decode($json);
    }
}
