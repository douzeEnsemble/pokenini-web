<?php

declare(strict_types=1);

namespace App\Service\Back;

use App\Utils\JsonDecoder;

class GetCatchStatesService extends AbstractBackService
{
    /**
     * @return string[][]
     */
    public function get(): array
    {
        $json = $this->requestContent(
            'GET',
            '/catch_states'
        );

        /** @var string[][] */
        return JsonDecoder::decode($json);
    }
}
