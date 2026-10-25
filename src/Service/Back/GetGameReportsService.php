<?php

declare(strict_types=1);

namespace App\Service\Back;

use App\Utils\JsonDecoder;

class GetGameReportsService extends AbstractBackService
{
    /** 6 levels of container nesting: root → games[] → game{} → hardest_pokemons[] → item{} → pokemon{} */
    private const int JSON_DEPTH = 7;

    /**
     * @return array<string, list<array<string, mixed>>>
     */
    public function get(): array
    {
        $json = $this->requestContent(
            'GET',
            '/istration/reports/games',
        );

        /** @var array<string, list<array<string, mixed>>> */
        return JsonDecoder::decode($json, self::JSON_DEPTH);
    }
}
