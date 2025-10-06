<?php

declare(strict_types=1);

namespace App\Service\Back;

use App\Utils\JsonDecoder;

class GetPokedexService extends AbstractBackService
{
    /**
     * @param string[]|string[][] $filters
     *
     * @return string[][]
     */
    public function get(
        string $dexSlug,
        array $filters = [],
    ): array {
        $url = "/album/{$dexSlug}";

        return $this->getData($url, $filters);
    }

    /**
     * @param string[]|string[][] $filters
     *
     * @return string[][]
     */
    public function getWithTrainerId(
        string $trainerId,
        string $dexSlug,
        array $filters = [],
    ): array {
        $url = "/album/{$dexSlug}";

        $filters['trainer_id'] = $trainerId;

        return $this->getData($url, $filters);
    }

    /**
     * @param string[]|string[][] $filters
     *
     * @return string[][]
     */
    private function getData(string $url, array $filters): array
    {
        $json = $this->requestContent(
            'GET',
            $url,
            [
                'query' => $filters,
            ],
        );

        /** @var string[][] */
        return JsonDecoder::decode($json);
    }
}
