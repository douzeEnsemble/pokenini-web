<?php

declare(strict_types=1);

namespace App\Service\Back;

use App\ResponseObject\Label\Labels;

class GetLabelsService extends AbstractBackService
{
    public function get(): Labels
    {
        $json = $this->requestContent(
            'GET',
            '/labels'
        );

        return $this->serializer->deserialize($json, Labels::class, 'json');
    }
}
