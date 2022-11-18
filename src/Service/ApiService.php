<?php

declare(strict_types=1);

namespace App\Service;

use App\Security\User;
use RuntimeException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\HttpClient\Exception\HttpExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class ApiService
{
    private const CACHE_KEY_SEPARATOR = '_';

    private const CACHE_KEY_CACHE_REGISTER = 'register';

    private const CACHE_KEY_DEXES = 'dexes';
    private const CACHE_KEY_CATCH_STATES = 'catch_states';
    private const CACHE_KEY_ALBUM = 'album';

    public function __construct(
        private readonly HttpClientInterface $client,
        private readonly string $appApiUrl,
        private readonly CacheInterface $cache
    ) {
    }

    /**
     * @return string[][]
     */
    public function getDexes(string $trainerId): array
    {
        $key = self::getDexesKey($trainerId);

        /** @var string $json */
        $json = $this->cache->get($key, function () use ($trainerId) {
            $response = $this->client->request(
                'GET',
                "{$this->appApiUrl}/dex/$trainerId/list",
                [
                    'headers' => [
                        'accept' => 'application/json',
                    ],
                ]
            );

            /** @var string */
            return $response->getContent();
        });

        $this->registerCache(self::CACHE_KEY_DEXES, $key);

        /** @var string[][] */
        return json_decode($json, true, 512, JSON_THROW_ON_ERROR);
    }

    /**
     * @return string[][][]
     */
    public function getPokedex(string $dexSlug, string $trainerId): array
    {
        $key = self::getPokedexKey($dexSlug, $trainerId);

        /** @var string $json */
        $json = $this->cache->get($key, function () use ($dexSlug, $trainerId) {
            $response = $this->client->request(
                'GET',
                "{$this->appApiUrl}/dex/$trainerId/$dexSlug"
            );

            /** @var string */
            return $response->getContent();
        });

        $this->registerCache(self::CACHE_KEY_ALBUM, $key);

        /** @var string[][][] */
        return json_decode($json, true, 512, JSON_THROW_ON_ERROR);
    }

    /**
     * @return string[][]
     */
    public function getCatchStates(): array
    {
        $key = self::getCatchStatesKey();

        /** @var string $json */
        $json = $this->cache->get($key, function () {
            $response = $this->client->request(
                'GET',
                "{$this->appApiUrl}/catch_states",
                [
                    'headers' => [
                        'accept' => 'application/json',
                    ],
                ]
            );

            /** @var string */
            return $response->getContent();
        });

        /** @var string[][] */
        return json_decode($json, true, 512, JSON_THROW_ON_ERROR);
    }

    /**
     * @throws TransportExceptionInterface
     * @throws HttpExceptionInterface
     */
    public function modifyAlbum(
        string $method,
        string $dexSlug,
        string $pokemonSlug,
        string $catchStateSlug,
        string $trainerId
    ): void {
        if (!in_array($method, [Request::METHOD_PATCH, Request::METHOD_PUT], true)) {
            throw new \InvalidArgumentException();
        }

        $this->client->request(
            $method,
            "{$this->appApiUrl}/album/$dexSlug/$pokemonSlug/u/$trainerId",
            [
                'body' => $catchStateSlug,
            ]
        );
    }

    /**
     * @throws TransportExceptionInterface
     * @throws HttpExceptionInterface
     */
    public function modifyDex(
        string $dexSlug,
        string $data,
        string $trainerId
    ): void {
        $this->client->request(
            'PUT',
            "{$this->appApiUrl}/dex/$trainerId/$dexSlug",
            [
                'body' => $data,
            ]
        );
    }

    public function invalidateCacheDexes(): void
    {
        $this->invalidateCacheByType(self::CACHE_KEY_DEXES);
    }

    public function invalidateCacheCatchStates(): void
    {
        $this->cache->delete(self::getCatchStatesKey());
    }

    public function invalidateCacheAlbums(): void
    {
        $this->invalidateCacheByType(self::CACHE_KEY_ALBUM);
    }

    public function invalidateCacheAlbum(string $dexSlug, string $trainerId): void
    {
        $key = self::getPokedexKey($dexSlug, $trainerId);

        $this->cache->delete($key);
        $this->unregisterCache(self::CACHE_KEY_ALBUM, $key);
    }

    private function registerCache(string $type, string $key): void
    {
        $registerKey = self::getRegisterTypeKey($type);

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

    private function unregisterCache(string $type, string $key): void
    {
        $registerKey = self::getRegisterTypeKey($type);

        /** @var string[] $list */
        $list = $this->cache->get($registerKey, function () {
            return [];
        });

        $listKey = array_search($key, $list, true);
        if (false !== $listKey) {
            unset($list[$listKey]);
            sort($list);
        }

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
        $key = self::getRegisterTypeKey($type);

        $list = $this->cache->get($key, function () {
            return [];
        });

        if (!is_array($list)) {
            return [];
        }

        return $list;
    }

    public function invalidateCacheByType(string $type): void
    {
        foreach ($this->getRegisteredCache($type) as $key) {
            $this->cache->delete($key);
        }

        $this->cache->delete(self::getRegisterTypeKey($type));
    }

    private static function getDexesKey(string $trainerId): string
    {
        return self::CACHE_KEY_DEXES . self::CACHE_KEY_SEPARATOR . $trainerId;
    }

    private static function getPokedexKey(string $dexSlug, string $trainerId): string
    {
        return self::CACHE_KEY_ALBUM . self::CACHE_KEY_SEPARATOR . $dexSlug . self::CACHE_KEY_SEPARATOR . $trainerId;
    }

    private static function getCatchStatesKey(): string
    {
        return self::CACHE_KEY_CATCH_STATES;
    }

    private static function getRegisterTypeKey(string $type): string
    {
        return self::CACHE_KEY_CACHE_REGISTER . self::CACHE_KEY_SEPARATOR . $type;
    }
}
