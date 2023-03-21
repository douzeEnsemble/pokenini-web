<?php

declare(strict_types=1);

namespace App\Service;

/**
 * @SuppressWarnings(PHPMD.CyclomaticComplexity)
 */
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
            case 'catch_states':
                $this->apiService->invalidateCacheCatchStates();
                $this->apiService->invalidateCacheActions();
                return;

            case 'games_and_dex':
            case 'dex':
                $this->apiService->invalidateCacheDex();
                $this->apiService->invalidateCacheActions();
                return;

            case 'pokemons':
                $this->apiService->invalidateCacheActions();
                return;

            case 'regional_dex_numbers':
            case 'games_availabilities':
            case 'game_bundles_availabilities':
            case 'albums':
                $this->apiService->invalidateCacheAlbums();
                $this->apiService->invalidateCacheActions();
                return;

            case 'dex_availabilities':
                $this->apiService->invalidateCacheDex();
                $this->apiService->invalidateCacheAlbums();
                $this->apiService->invalidateCacheActions();
                return;

            case 'actions':
                $this->apiService->invalidateCacheActions();
                return;

            case 'reports':
                $this->apiService->invalidateCacheReports();
                return;
        }

        throw new \InvalidArgumentException();
    }
}
