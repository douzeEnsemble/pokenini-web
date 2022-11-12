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
    public function getDexes(string $userId): array
    {
        $key = self::CACHE_KEY_DEXES . self::CACHE_KEY_SEPARATOR . $userId;

        /** @var string $json */
        $json = $this->cache->get($key, function () use ($userId) {
            $response = $this->client->request(
                'GET',
                "{$this->appApiUrl}/dexes/u/$userId",
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
    public function getPokedex(string $dexSlug, string $userId): array
    {
        $key = self::CACHE_KEY_ALBUM . self::CACHE_KEY_SEPARATOR . $dexSlug . self::CACHE_KEY_SEPARATOR . $userId;

        /** @var string $json */
        $json = $this->cache->get($key, function () use ($dexSlug, $userId) {
            $response = $this->client->request(
                'GET',
                "{$this->appApiUrl}/album/$dexSlug/u/$userId"
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
        /** @var string $json */
        $json = $this->cache->get(self::CACHE_KEY_CATCH_STATES, function () {
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
        string $userId
    ): void {
        if (!in_array($method, [Request::METHOD_PATCH, Request::METHOD_PUT], true)) {
            throw new \InvalidArgumentException();
        }

        $this->client->request(
            $method,
            "{$this->appApiUrl}/album/$dexSlug/$pokemonSlug/u/$userId",
            [
                'body' => $catchStateSlug,
            ]
        );
    }

    public function invalidateCacheDexes(): void
    {
        $this->invalidateCacheByType(self::CACHE_KEY_DEXES);
    }

    public function invalidateCacheCatchStates(): void
    {
        $this->cache->delete(self::CACHE_KEY_CATCH_STATES);
    }

    public function invalidateCacheAlbums(): void
    {
        $this->invalidateCacheByType(self::CACHE_KEY_ALBUM);
    }

    private function registerCache(string $type, string $key): void
    {
        $registerKey = self::CACHE_KEY_CACHE_REGISTER . self::CACHE_KEY_SEPARATOR . $type;

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

    /**
     * @return string[]
     */
    private function getRegisteredCache(string $type): array
    {
        $key = self::CACHE_KEY_CACHE_REGISTER . self::CACHE_KEY_SEPARATOR . $type;

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

        $this->cache->delete(self::CACHE_KEY_CACHE_REGISTER . self::CACHE_KEY_SEPARATOR . $type);
    }
}
