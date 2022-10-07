<?php

declare(strict_types=1);

namespace App\Service;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\HttpExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class ApiService
{
    private const CACHE_KEY_DEXES = 'dexes';
    private const CACHE_KEY_CATCH_STATES = 'catch_states';
    private const CACHE_KEY_ALBUM = 'album_';

    public function __construct(
        private readonly HttpClientInterface $client,
        private readonly string $appApiUrl,
        private readonly CacheInterface $cache
    ) {
    }

    /**
     * @return string[][]
     */
    public function getDexes(): array
    {
        /** @var string $json */
        $json = $this->cache->get(self::CACHE_KEY_DEXES, function () {
            $response = $this->client->request(
                'GET',
                "{$this->appApiUrl}/dexes",
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
     * @return string[][]|string[][][]
     */
    public function getPokedex(string $dexSlug): array
    {
        /** @var string $json */
        $json = $this->cache->get(self::CACHE_KEY_ALBUM . $dexSlug, function () use ($dexSlug) {
            $response = $this->client->request(
                'GET',
                "{$this->appApiUrl}/album/$dexSlug"
            );

            /** @var string */
            return $response->getContent();
        });

        /** @var string[][] */
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
        string $catchStateSlug
    ): void {
        if (!in_array($method, [Request::METHOD_PATCH, Request::METHOD_PUT], true)) {
            throw new \InvalidArgumentException();
        }

        $this->client->request(
            $method,
            "{$this->appApiUrl}/album/$dexSlug/$pokemonSlug",
            [
                'body' => $catchStateSlug,
            ]
        );
    }

    public function invalidateCacheDexes(): void
    {
        $this->cache->delete(self::CACHE_KEY_DEXES);
    }

    public function invalidateCacheCatchStates(): void
    {
        $this->cache->delete(self::CACHE_KEY_CATCH_STATES);
    }

    public function invalidateCacheAlbums(): void
    {
        $dexes = $this->getDexes();

        foreach ($dexes as $dex) {
            $this->cache->delete(self::CACHE_KEY_ALBUM . $dex['slug']);
        }
    }
}
