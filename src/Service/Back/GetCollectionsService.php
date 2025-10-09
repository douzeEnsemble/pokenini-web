<?php

declare(strict_types=1);

namespace App\Service\Back;

use App\Utils\JsonDecoder;

class GetCollectionsService extends AbstractBackService
{
    /**
     * @return string[][]
     */
    public function get(): array
    {
        $json = $this->requestContent(
            'GET',
            '/labels/collections',
        );

        /** @var string[][] */
        return JsonDecoder::decode($json);
    }
}
