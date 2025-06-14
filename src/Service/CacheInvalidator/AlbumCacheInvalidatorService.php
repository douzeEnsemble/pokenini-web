<?php

declare(strict_types=1);

namespace App\Service\CacheInvalidator;

use App\Cache\KeyMaker;

class AlbumCacheInvalidatorService extends AbstractCacheInvalidatorService
{
    public function invalidate(string $dexSlug, string $trainerId): void
    {
        $key = KeyMaker::getPokedexKey($dexSlug, $trainerId);

        $this->cache->delete($key);
        $this->cache->invalidateTags([
            KeyMaker::getTrainerIdKey($trainerId),
        ]);
    }
}
