<?php

namespace App\Service;

use App\Security\UserTokenService;
use App\Service\Api\GetPokedexService;
use Symfony\Contracts\HttpClient\Exception\HttpExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class GetTrainerPokedexService
{
    public function __construct(
        private readonly UserTokenService $userTokenService,
        private readonly GetPokedexService $getPokedexService,
    ) {}

    /**
     * @param string[]|string[][] $filters
     *
     * @return null|string[][]
     */
    public function getPokedexData(string $dexSlug, array $filters): ?array
    {
        $trainerId = $this->userTokenService->getLoggedUserToken();

        return $this->getPokedexDataByTrainerId($dexSlug, $filters, $trainerId);
    }

    /**
     * @param string[]|string[][] $filters
     *
     * @return null|string[][]
     */
    public function getPokedexDataByTrainerId(string $dexSlug, array $filters, string $trainerId): ?array
    {
        try {
            return $this->getPokedexService->get($dexSlug, $trainerId, $filters);
        } catch (HttpExceptionInterface|TransportExceptionInterface $e) {
            return null;
        }
    }
}
