<?php

namespace App\Service;

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
}
