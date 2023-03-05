<?php

declare(strict_types=1);

namespace App\Service;

use App\Cache\KeyMaker;
use App\Utils\JsonDecoder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\HttpClient\Exception\HttpExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class ApiService
{
    public function __construct(
        private readonly HttpClientInterface $client,
        private readonly string $appApiUrl,
        private readonly CacheInterface $cache,
        private readonly string $apiLogin,
        private readonly string $apiPassword
    ) {
    }

    /**
     * @return string[][]
     */
    public function getDex(string $trainerId): array
    {
        return $this->getDexWithParam($trainerId, '');
    }

    /**
     * @return string[][]
     */
    public function getDexWithUnreleased(string $trainerId): array
    {
        return $this->getDexWithParam($trainerId, 'include_unreleased_dex=1');
    }

    /**
     * @return string[][][]
     */
    public function getPokedex(string $dexSlug, string $trainerId): array
    {
        $key = KeyMaker::getPokedexKey($dexSlug, $trainerId);

        /** @var string $json */
        $json = $this->cache->get($key, function () use ($dexSlug, $trainerId) {
            $response = $this->client->request(
                'GET',
                "{$this->appApiUrl}/album/$trainerId/$dexSlug",
                [
                    'auth_basic' => [
                        $this->apiLogin,
                        $this->apiPassword,
                    ],
                ]
            );

            /** @var string */
            return $response->getContent();
        });

        $this->registerCache(KeyMaker::getAlbumKey(), $key);

        /** @var string[][][] */
        return JsonDecoder::decode($json);
    }

    /**
     * @return string[][]
     */
    public function getCatchStates(): array
    {
        $key = KeyMaker::getCatchStatesKey();

        /** @var string $json */
        $json = $this->cache->get($key, function () {
            $response = $this->client->request(
                'GET',
                "{$this->appApiUrl}/catch_states",
                [
                    'headers' => [
                        'accept' => 'application/json',
                    ],
                    'auth_basic' => [
                        $this->apiLogin,
                        $this->apiPassword,
                    ],
                ]
            );

            /** @var string */
            return $response->getContent();
        });

        /** @var string[][] */
        return JsonDecoder::decode($json);
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
            "{$this->appApiUrl}/album/$trainerId/$dexSlug/$pokemonSlug",
            [
                'body' => $catchStateSlug,
                'auth_basic' => [
                    $this->apiLogin,
                    $this->apiPassword,
                ],
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
                'auth_basic' => [
                    $this->apiLogin,
                    $this->apiPassword,
                ],
            ]
        );
    }

    public function adminUpdate(string $type): string
    {
        $response = $this->client->request(
            'POST',
            "{$this->appApiUrl}/istration/update/$type",
            [
                'auth_basic' => [
                    $this->apiLogin,
                    $this->apiPassword,
                ],
            ]
        );

        /** @var string */
        return $response->getContent();
    }

    public function adminCalculate(string $type): string
    {
        $response = $this->client->request(
            'POST',
            "{$this->appApiUrl}/istration/calculate/$type",
            [
                'auth_basic' => [
                    $this->apiLogin,
                    $this->apiPassword,
                ],
            ]
        );

        /** @var string */
        return $response->getContent();
    }

    /**
     * @return string[][]
     */
    public function getReports(): array
    {
        $key = KeyMaker::getReportsKey();

        /** @var string $json */
        $json = $this->cache->get($key, function () {
            $response = $this->client->request(
                'GET',
                "{$this->appApiUrl}/reports",
                [
                    'headers' => [
                        'accept' => 'application/json',
                    ],
                    'auth_basic' => [
                        $this->apiLogin,
                        $this->apiPassword,
                    ],
                ]
            );

            /** @var string */
            return $response->getContent();
        });

        /** @var string[][] */
        return JsonDecoder::decode($json);
    }

    public function invalidateCacheDex(): void
    {
        $this->invalidateCacheByType(KeyMaker::getDexKey());
    }

    public function invalidateCacheCatchStates(): void
    {
        $this->cache->delete(KeyMaker::getCatchStatesKey());
    }

    public function invalidateCacheAlbums(): void
    {
        $this->invalidateCacheByType(KeyMaker::getAlbumKey());
    }

    public function invalidateCacheDexByTrainerId(string $trainerId): void
    {
        $key = KeyMaker::getDexKeyForTrainer($trainerId);

        $this->cache->delete($key);
        $this->unregisterCache(KeyMaker::getDexKey(), $key);
    }

    public function invalidateCacheAlbum(string $dexSlug, string $trainerId): void
    {
        $key = KeyMaker::getPokedexKey($dexSlug, $trainerId);

        $this->cache->delete($key);
        $this->unregisterCache(KeyMaker::getAlbumKey(), $key);
    }

    public function invalidateCacheReports(): void
    {
        $this->cache->delete(KeyMaker::getReportsKey());
    }

    private function invalidateCacheByType(string $type): void
    {
        foreach ($this->getRegisteredCache($type) as $key) {
            $this->cache->delete($key);
        }

        $this->cache->delete(KeyMaker::getRegisterTypeKey($type));
    }

    private function registerCache(string $type, string $key): void
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

    private function unregisterCache(string $type, string $key): void
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
        sort($list);

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

    /**
     * @return string[][]
     */
    private function getDexWithParam(string $trainerId, string $queryParams = ''): array
    {
        $key = KeyMaker::getDexKeyForTrainer($trainerId, $queryParams);

        /** @var string $json */
        $json = $this->cache->get($key, function () use ($trainerId, $queryParams) {
            $response = $this->client->request(
                'GET',
                "{$this->appApiUrl}/dex/$trainerId/list" . (!empty($queryParams) ? '?' . $queryParams : ''),
                [
                    'headers' => [
                        'accept' => 'application/json',
                    ],
                    'auth_basic' => [
                        $this->apiLogin,
                        $this->apiPassword,
                    ],
                ]
            );

            /** @var string */
            return $response->getContent();
        });

        $this->registerCache(KeyMaker::getDexKey(), $key);

        /** @var string[][] */
        return JsonDecoder::decode($json);
    }
}
