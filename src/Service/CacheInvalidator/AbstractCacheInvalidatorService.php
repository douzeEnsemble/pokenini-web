<?php

declare(strict_types=1);

namespace App\Service\CacheInvalidator;

use App\Cache\KeyMaker;
use App\Service\Trait\CacheRegisterTrait;
use Symfony\Contracts\Cache\CacheInterface;

class AbstractCacheInvalidatorService
{
    use CacheRegisterTrait;

    public function __construct(
        protected readonly CacheInterface $cache,
    ) {
    }

    protected function invalidateCacheByType(string $type): void
    {
        foreach ($this->getRegisteredCache($type) as $key) {
            $this->cache->delete($key);
        }

        $this->cache->delete(KeyMaker::getRegisterTypeKey($type));
    }
}
