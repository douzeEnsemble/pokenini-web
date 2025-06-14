<?php

declare(strict_types=1);

namespace App\Service;

use App\Service\CacheInvalidator\AlbumsCacheInvalidatorService;
use App\Service\CacheInvalidator\CatchStatesCacheInvalidatorService;
use App\Service\CacheInvalidator\DexCacheInvalidatorService;
use App\Service\CacheInvalidator\FormsCacheInvalidatorService;
use App\Service\CacheInvalidator\ReportsCacheInvalidatorService;
use App\Service\CacheInvalidator\TypesCacheInvalidatorService;

/**
 * @SuppressWarnings(PHPMD.CyclomaticComplexity)
 */
class CacheInvalidatorService
{
    public function __construct(
        private readonly CatchStatesCacheInvalidatorService $catchStatesCacheInvalidatorService,
        private readonly TypesCacheInvalidatorService $typesCacheInvalidatorService,
        private readonly FormsCacheInvalidatorService $formsCacheInvalidatorService,
        private readonly DexCacheInvalidatorService $dexCacheInvalidatorService,
        private readonly AlbumsCacheInvalidatorService $albumsCacheInvalidatorService,
        private readonly ReportsCacheInvalidatorService $reportsCacheInvalidatorService,
    ) {}

    public function invalidate(string $type): void
    {
        switch ($type) {
            case 'labels':
                $this->catchStatesCacheInvalidatorService->invalidate();
                $this->typesCacheInvalidatorService->invalidate();
                $this->formsCacheInvalidatorService->invalidate();

                return;

            case 'games_collections_and_dex':
            case 'dex':
                $this->dexCacheInvalidatorService->invalidate();

                return;

            case 'pokemons':
                return;

            case 'regional_dex_numbers':
            case 'games_availabilities':
            case 'games_shinies_availabilities':
            case 'game_bundles_availabilities':
            case 'game_bundles_shinies_availabilities':
            case 'collections_availabilities':
            case 'pokemon_availabilities':
            case 'albums':
                $this->albumsCacheInvalidatorService->invalidate();

                return;

            case 'dex_availabilities':
                $this->dexCacheInvalidatorService->invalidate();
                $this->albumsCacheInvalidatorService->invalidate();

                return;

            case 'reports':
                $this->reportsCacheInvalidatorService->invalidate();

                return;
        }

        throw new \InvalidArgumentException();
    }
}
