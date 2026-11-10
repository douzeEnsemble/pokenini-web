<?php

declare(strict_types=1);

namespace App\Service\Back;

use App\ResponseObject\Election\ElectionIndex;

class GetElectionIndexService extends AbstractBackService
{
    /**
     * @param string[]|string[][] $filters
     * @param null|string         $locale  the language of the names, null for both languages
     */
    public function get(
        string $dexSlug,
        string $electionSlug,
        array $filters,
        ?string $locale = null,
    ): ElectionIndex {
        $path = empty($electionSlug)
            ? "/election/{$dexSlug}"
            : "/election/{$dexSlug}/{$electionSlug}";

        $json = $this->requestContent(
            'GET',
            $path,
            [
                'query' => null === $locale ? $filters : $filters + ['lang' => $locale],
            ]
        );

        return $this->serializer->deserialize($json, ElectionIndex::class, 'json');
    }
}
