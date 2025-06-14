<?php

declare(strict_types=1);

namespace App\Service\Api;

use App\Cache\KeyMaker;
use App\Utils\JsonDecoder;
use Symfony\Contracts\Cache\ItemInterface;

class GetDexService extends AbstractApiService
{
    /**
     * @return string[][]
     */
    public function get(string $trainerId): array
    {
        return $this->getDexWithParam($trainerId, []);
    }

    /**
     * @return string[][]
     */
    public function getWithUnreleased(string $trainerId): array
    {
        return $this->getDexWithParam($trainerId, [
            'include_unreleased_dex' => '1',
        ]);
    }

    /**
     * @return string[][]
     */
    public function getWithPremium(string $trainerId): array
    {
        return $this->getDexWithParam($trainerId, [
            'include_premium_dex' => '1',
        ]);
    }

    /**
     * @return string[][]
     */
    public function getWithUnreleasedAndPremium(string $trainerId): array
    {
        return $this->getDexWithParam($trainerId, [
            'include_unreleased_dex' => '1',
            'include_premium_dex' => '1',
        ]);
    }

    /**
     * @param string[] $queryParams
     *
     * @return string[][]
     */
    private function getDexWithParam(string $trainerId, array $queryParams = []): array
    {
        $key = KeyMaker::getDexKeyForTrainer($trainerId, $queryParams);

        $urlQueryParams = http_build_query($queryParams);

        /** @var string $json */
        $json = $this->cache->get($key, function (ItemInterface $item) use ($trainerId, $urlQueryParams) {
            $item->tag([
                KeyMaker::getDexKey(),
                KeyMaker::getTrainerIdKey($trainerId),
            ]);

            return $this->requestContent(
                'GET',
                "/dex/{$trainerId}/list".($urlQueryParams ? '?'.$urlQueryParams : ''),
            );
        });

        /** @var string[][] */
        return JsonDecoder::decode($json);
    }
}
