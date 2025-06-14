<?php

declare(strict_types=1);

namespace App\Service\Api;

use App\Utils\JsonDecoder;

class ElectionMetricsApiService extends AbstractApiService
{
    /**
     * @return float[]|int[]
     */
    public function getMetrics(
        string $trainerId,
        string $dexSlug,
        string $electionSlug,
    ): array {
        /** @var string $json */
        $json = $this->requestContent(
            'GET',
            '/election/metrics',
            [
                'query' => [
                    'trainer_external_id' => $trainerId,
                    'dex_slug' => $dexSlug,
                    'election_slug' => $electionSlug,
                ],
            ],
        );

        /** @var float[]|int[] */
        return JsonDecoder::decode($json);
    }
}
