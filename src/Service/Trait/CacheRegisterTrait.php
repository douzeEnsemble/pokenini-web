<?php

declare(strict_types=1);

namespace App\Service\Trait;

use App\Cache\KeyMaker;

trait CacheRegisterTrait
{
    protected function registerCache(string $type, string $key): void
    {
        $registerKey = KeyMaker::getRegisterTypeKey($type);

        /** @var string[] $list */
        $list = $this->cache->get($registerKey, function () {
            return [];
        });

        $list[] = $key;
        $list = array_unique($list);

        $this->cache->delete($registerKey);
        $this->cache->get($registerKey, function () use ($list) {
            return $list;
        });
    }

    protected function unregisterCache(string $type, string $key): void
    {
        $registerKey = KeyMaker::getRegisterTypeKey($type);

        /** @var string[] $list */
        $list = $this->cache->get($registerKey, function () {
            // @codeCoverageIgnoreStart
            return [];
            // @codeCoverageIgnoreEnd
        });

        $listKey = array_search($key, $list, true);
        unset($list[$listKey]);

        $this->cache->delete($registerKey);
        $this->cache->get($registerKey, function () use ($list) {
            return $list;
        });
    }

    /**
     * @return string[]
     */
    private function getRegisteredCache(string $type): array
    {
        $key = KeyMaker::getRegisterTypeKey($type);

        $list = $this->cache->get($key, function () {
            return [];
        });

        if (!is_array($list)) {
            // @codeCoverageIgnoreStart
            return [];
            // @codeCoverageIgnoreEnd
        }

        return $list;
    }
}
