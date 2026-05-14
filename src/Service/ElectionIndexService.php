<?php

declare(strict_types=1);

namespace App\Service;

use App\DTO\ElectionIndexData;
use App\DTO\ElectionMetrics;
use App\DTO\ElectionTop;
use App\Service\Back\GetElectionIndexService;
use Symfony\Contracts\HttpClient\Exception\HttpExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class ElectionIndexService
{
    public function __construct(private readonly GetElectionIndexService $apiService) {}

    /**
     * @param string[]|string[][] $filters
     */
    public function get(string $dexSlug, string $electionSlug, array $filters): ?ElectionIndexData
    {
        try {
            $response = $this->apiService->get($dexSlug, $electionSlug, $filters);
        } catch (HttpExceptionInterface|TransportExceptionInterface $e) {
            return null;
        }

        return new ElectionIndexData(
            $response->getType(),
            $response->getPokemons(),
            $response->getPokedex(),
            new ElectionTop($response->getElectionTop()),
            ElectionMetrics::createFromArray($response->getMetrics()),
            $response->getDetachedCount(),
            $response->isTheLastOne(),
            $response->isTheLastPage(),
        );
    }
}
