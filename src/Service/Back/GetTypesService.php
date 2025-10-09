<?php

declare(strict_types=1);

namespace App\Service\Back;

use App\Utils\JsonDecoder;

class GetTypesService extends AbstractBackService
{
    /**
     * @return string[][]
     */
    public function get(): array
    {
        $json = $this->requestContent(
            'GET',
            '/labels/types',
        );

        /** @var string[][] */
        return JsonDecoder::decode($json);
    }
}
