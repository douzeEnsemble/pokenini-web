<?php

declare(strict_types=1);

namespace App\Service\CacheInvalidator;

use Symfony\Contracts\Cache\TagAwareCacheInterface;

class AbstractCacheInvalidatorService
{
    public function __construct(
        protected readonly TagAwareCacheInterface $cache,
    ) {}
}
