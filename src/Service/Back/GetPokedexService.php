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
        string $trainerId,
        array $filters = [],
    ): array {
        $url = "/album/{$trainerId}/{$dexSlug}";

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
