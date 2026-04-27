<?php

declare(strict_types=1);

namespace App\Service\Back;

use App\ResponseObject\Election\ElectionIndex;

class GetElectionIndexService extends AbstractBackService
{
    /**
     * @param string[]|string[][] $filters
     */
    public function get(
        string $dexSlug,
        string $electionSlug,
        array $filters,
    ): ElectionIndex {
        $path = empty($electionSlug)
            ? "/election/{$dexSlug}"
            : "/election/{$dexSlug}/{$electionSlug}";

        $json = $this->requestContent(
            'GET',
            $path,
            [
                'query' => $filters,
            ]
        );

        return $this->serializer->deserialize($json, ElectionIndex::class, 'json');
    }
}
