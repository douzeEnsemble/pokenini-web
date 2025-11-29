<?php

declare(strict_types=1);

namespace App\Service\Back;

use App\ResponseObject\Election\ElectionList;

class GetPokemonsService extends AbstractBackService
{
    /**
     * @param string[]|string[][] $filters
     */
    public function get(
        string $dexSlug,
        string $electionSlug,
        int $count,
        array $filters,
    ): ElectionList {
        $json = $this->requestContent(
            'GET',
            '/pokemons/to_choose',
            [
                'query' => array_merge(
                    [
                        'dex_slug' => $dexSlug,
                        'election_slug' => $electionSlug,
                        'count' => $count,
                    ],
                    $filters,
                ),
            ]
        );

        return $this->serializer->deserialize($json, ElectionList::class, 'json');
    }
}
