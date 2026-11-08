<?php

declare(strict_types=1);

namespace App\Service\Back;

use App\ResponseObject\Label\Labels;

class GetLabelsService extends AbstractBackService
{
    /**
     * @param string $locale the language of the names of the labels
     */
    public function get(string $locale): Labels
    {
        $json = $this->requestContent(
            'GET',
            '/labels',
            ['query' => ['lang' => $locale]],
        );

        return $this->serializer->deserialize($json, Labels::class, 'json');
    }
}
