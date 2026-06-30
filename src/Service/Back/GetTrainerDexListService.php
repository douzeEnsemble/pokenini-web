<?php

declare(strict_types=1);

namespace App\Service\Back;

use App\ResponseObject\Album\DexListItem;

class GetTrainerDexListService extends AbstractBackService
{
    /** @return DexListItem[] */
    public function get(): array
    {
        $json = $this->requestContent('GET', '/trainer/dex');

        /** @var DexListItem[] */
        return $this->serializer->deserialize($json, DexListItem::class.'[]', 'json');
    }
}
