<?php

declare(strict_types=1);

namespace App\Service\Back;

use App\Utils\JsonDecoder;

class GetTrainerDexListService extends AbstractBackService
{
    /**
     * @return string[][]
     */
    public function get(): array
    {
        $json = $this->requestContent(
            'GET',
            '/trainer/dex',
        );

        /** @var string[][] */
        return JsonDecoder::decode($json);
    }
}
