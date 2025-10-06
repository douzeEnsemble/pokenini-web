<?php

namespace App\Service;

use App\Service\Back\GetPokedexService;
use Symfony\Contracts\HttpClient\Exception\HttpExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class GetTrainerPokedexService
{
    public function __construct(
        private readonly GetPokedexService $getPokedexService,
    ) {}

    /**
     * @param string[]|string[][] $filters
     *
     * @return null|string[][]
     */
    public function getPokedexData(string $dexSlug, array $filters, ?string $trainerId = null): ?array
    {
        try {
            if (null !== $trainerId && !empty($trainerId)) {
                return $this->getPokedexService->getWithTrainerId($trainerId, $dexSlug, $filters);
            }

            return $this->getPokedexService->get($dexSlug, $filters);
        } catch (HttpExceptionInterface|TransportExceptionInterface $e) {
            return null;
        }
    }
}
