<?php

declare(strict_types=1);

namespace App\Service\Back;

use App\ResponseObject\Album\Album;

class GetPokedexService extends AbstractBackService
{
    /**
     * @param string[]|string[][] $filters
     */
    public function get(
        string $dexSlug,
        array $filters = [],
    ): Album {
        $url = "/album/{$dexSlug}";

        return $this->getData($url, $filters);
    }

    /**
     * @param string[]|string[][] $filters
     */
    public function getWithTrainerId(
        string $trainerId,
        string $dexSlug,
        array $filters = [],
    ): Album {
        $url = "/album/{$dexSlug}";

        $filters['trainer_id'] = $trainerId;

        return $this->getData($url, $filters);
    }

    /**
     * @param string[]|string[][] $filters
     */
    private function getData(string $url, array $filters): Album
    {
        $json = $this->requestContent(
            'GET',
            $url,
            [
                'query' => $filters,
            ],
        );

        return $this->serializer->deserialize($json, Album::class, 'json');
    }
}
