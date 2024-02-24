<?php

declare(strict_types=1);

namespace App\Service\CacheInvalidator;

use App\Cache\KeyMaker;

class TypesCacheInvalidatorService extends AbstractCacheInvalidatorService
{
    public function invalidate(): void
    {
        $this->cache->delete(KeyMaker::getTypesKey());
    }
}
