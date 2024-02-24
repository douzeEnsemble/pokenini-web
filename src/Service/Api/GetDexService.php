<?php

declare(strict_types=1);

namespace App\Service\Api;

use App\Cache\KeyMaker;
use App\Utils\JsonDecoder;

class GetDexService extends AbstractApiService
{
    /**
     * @return string[][]
     */
    public function get(string $trainerId): array
    {
        return $this->getDexWithParam($trainerId, '');
    }

    /**
     * @return string[][]
     */
    public function getWithUnreleased(string $trainerId): array
    {
        return $this->getDexWithParam($trainerId, 'include_unreleased_dex=1');
    }

    /**
     * @return string[][]
     */
    private function getDexWithParam(string $trainerId, string $queryParams = ''): array
    {
        $key = KeyMaker::getDexKeyForTrainer($trainerId, $queryParams);

        /** @var string $json */
        $json = $this->cache->get($key, function () use ($trainerId, $queryParams) {
            return $this->requestContent(
                'GET',
                "/dex/$trainerId/list" . (!empty($queryParams) ? '?' . $queryParams : ''),
            );
        });

        $this->registerCache(KeyMaker::getDexKey(), $key);

        /** @var string[][] */
        return JsonDecoder::decode($json);
    }
}
