<?php

declare(strict_types=1);

namespace App\Service\Back;

use App\Utils\JsonDecoder;

class GetElectionDexListService extends AbstractBackService
{
    /**
     * @return string[][]
     */
    public function get(): array
    {
        $json = $this->requestContent(
            'GET',
            '/dex/can_hold_election',
        );

        /** @var string[][] */
        return JsonDecoder::decode($json);
    }
}
