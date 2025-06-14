<?php

declare(strict_types=1);

namespace App\Service\CacheInvalidator;

use App\Cache\KeyMaker;

class DexCacheInvalidatorService extends AbstractCacheInvalidatorService
{
    public function invalidate(): void
    {
        $this->cache->invalidateTags([
            KeyMaker::getDexKey(),
        ]);
    }

    public function invalidateByTrainerId(string $trainerId): void
    {
        $key = KeyMaker::getDexKeyForTrainer($trainerId);

        $this->cache->delete($key);
        $this->cache->invalidateTags([
            KeyMaker::getTrainerIdKey($trainerId),
        ]);
    }
}
