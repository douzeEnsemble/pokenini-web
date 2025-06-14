<?php

declare(strict_types=1);

namespace App\Service\Api;

use App\Cache\KeyMaker;
use App\Utils\JsonDecoder;
use Symfony\Contracts\Cache\ItemInterface;

class GetPokedexService extends AbstractApiService
{
    /**
     * @param string[]|string[][] $filters
     *
     * @return string[][]
     */
    public function get(
        string $dexSlug,
        string $trainerId,
        array $filters = [],
    ): array {
        $key = KeyMaker::getPokedexKey($dexSlug, $trainerId, $filters);

        /** @var string $json */
        $json = $this->cache->get($key, function (ItemInterface $item) use ($dexSlug, $trainerId, $filters) {
            $item->tag([
                KeyMaker::getAlbumKey(),
                KeyMaker::getTrainerIdKey($trainerId),
            ]);

            $url = "/album/{$trainerId}/{$dexSlug}";

            return $this->requestContent(
                'GET',
                $url,
                [
                    'query' => $filters,
                ],
            );
        });

        /** @var string[][] */
        return JsonDecoder::decode($json);
    }
}
