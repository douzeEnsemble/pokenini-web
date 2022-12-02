<?php

declare(strict_types=1);

namespace App\Service;

class CacheInvalidatorService
{
    public function __construct(
        private readonly ApiService $apiService
    ) {
    }

    public function invalidate(string $type): void
    {
        switch ($type) {
            case 'labels':
                $this->apiService->invalidateCacheCatchStates();
                return;

            case 'games_and_dexes':
                $this->apiService->invalidateCacheDexes();
                return;

            case 'pokemons':
                return;

            case 'regional_dex_number':
            case 'game_availability':
            case 'game_bundle_availability':
                $this->apiService->invalidateCacheAlbums();
                return;

            case 'dex_availability':
                $this->apiService->invalidateCacheDexes();
                $this->apiService->invalidateCacheAlbums();
                return;
        }

        throw new \InvalidArgumentException();
    }
}
