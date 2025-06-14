<?php

declare(strict_types=1);

namespace App\Service\CacheInvalidator;

use App\Cache\KeyMaker;

class FormsCacheInvalidatorService extends AbstractCacheInvalidatorService
{
    public function invalidate(): void
    {
        $this->cache->delete(KeyMaker::getFormsCategoryKey());
        $this->cache->delete(KeyMaker::getFormsRegionalKey());
        $this->cache->delete(KeyMaker::getFormsSpecialKey());
        $this->cache->delete(KeyMaker::getFormsVariantKey());
    }
}
