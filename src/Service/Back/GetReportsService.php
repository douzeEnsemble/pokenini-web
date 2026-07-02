<?php

declare(strict_types=1);

namespace App\Service\Back;

use App\Utils\JsonDecoder;

class GetReportsService extends AbstractBackService
{
    /**
     * @return array<string, list<array<string, mixed>>>
     */
    public function get(): array
    {
        $json = $this->requestContent(
            'GET',
            '/istration/reports',
        );

        /** @var array<string, list<array<string, mixed>>> */
        return JsonDecoder::decode($json);
    }
}
