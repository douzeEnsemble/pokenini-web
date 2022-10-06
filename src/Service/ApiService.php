<?php

declare(strict_types=1);

namespace App\Service;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class ApiService
{
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
        $json = $this->cache->get('dexes', function () {
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
        $json = $this->cache->get("album_$dexSlug", function () use ($dexSlug) {
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
        $json = $this->cache->get("catch_states", function () {
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

    public function modifyAlbum(
        string $method,
        string $dexSlug,
        string $pokemonSlug,
        string $catchStateSlug
    ): void {
        if (!in_array($method, [Request::METHOD_PATCH, Request::METHOD_PUT])) {
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
}
