<?php

declare(strict_types=1);

namespace App\Service\Back;

use App\ResponseObject\Election\ElectionDexListItem;

class GetElectionDexListService extends AbstractBackService
{
    /** @return ElectionDexListItem[] */
    public function get(): array
    {
        $json = $this->requestContent('GET', '/election/dex');

        /** @var ElectionDexListItem[] */
        return $this->serializer->deserialize($json, ElectionDexListItem::class.'[]', 'json');
    }
}
