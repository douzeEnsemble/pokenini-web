<?php

declare(strict_types=1);

namespace App\Service\Back;

use App\DTO\ElectionTop;
use App\ResponseObject\Election\TopPokemon;

class GetElectionTopService extends AbstractBackService
{
    public function getTop(
        string $dexSlug,
        string $electionSlug,
        int $count,
    ): ElectionTop {
        /** @var string $json */
        $json = $this->requestContent(
            'GET',
            "/election/top?dex_slug={$dexSlug}&election_slug={$electionSlug}&count={$count}"
        );

        /** @var TopPokemon[] $items */
        $items = $this->serializer->deserialize($json, TopPokemon::class.'[]', 'json');

        return new ElectionTop($items);
    }
}
