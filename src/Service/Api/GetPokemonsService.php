<?php

declare(strict_types=1);

namespace App\Service\Api;

use App\DTO\ElectionPokemonsList;
use App\Utils\JsonDecoder;

class GetPokemonsService extends AbstractApiService
{
    /**
     * @param string[]|string[][] $filters
     */
    public function get(
        string $trainerExternalId,
        string $dexSlug,
        string $electionSlug,
        int $count,
        array $filters,
    ): ElectionPokemonsList {
        /** @var string $json */
        $json = $this->requestContent(
            'GET',
            '/pokemons/to_choose',
            [
                'query' => array_merge(
                    [
                        'trainer_external_id' => $trainerExternalId,
                        'dex_slug' => $dexSlug,
                        'election_slug' => $electionSlug,
                        'count' => $count,
                    ],
                    $filters,
                ),
            ]
        );

        /** @var array{type: string, items: list<array{null|int|string}>} */
        $data = JsonDecoder::decode($json);

        return new ElectionPokemonsList($data);
    }
}
