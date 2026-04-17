<?php

declare(strict_types=1);

namespace App\Service;

use App\ResponseObject\Album\Album;
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
     */
    public function getPokedexData(string $dexSlug, array $filters, ?string $trainerId = null): ?Album
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
