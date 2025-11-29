<?php

namespace App\Service;

use App\ResponseObject\Election\ElectionList;
use App\Service\Back\GetPokemonsService;

class GetPokemonsListService
{
    public function __construct(
        private readonly GetPokemonsService $getPokemonsService,
        private readonly int $electionCandidateCount,
    ) {}

    /**
     * @param string[]|string[][] $filters
     */
    public function get(string $dexSlug, string $electionSlug, array $filters): ElectionList
    {
        return $this->getPokemonsService->get(
            $dexSlug,
            $electionSlug,
            $this->electionCandidateCount,
            $filters,
        );
    }
}
