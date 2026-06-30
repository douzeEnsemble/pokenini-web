<?php

declare(strict_types=1);

namespace App\Service\Back;

use App\ResponseObject\Album\DexListItem;

class GetAlbumDexListService extends AbstractBackService
{
    /**
     * @return DexListItem[]
     */
    public function get(?string $trainerId = null): array
    {
        $options = (null !== $trainerId && '' !== $trainerId) ? ['query' => ['trainer_id' => $trainerId]] : [];

        $json = $this->requestContent(
            'GET',
            '/album/dex',
            $options,
        );

        /** @var DexListItem[] */
        return $this->serializer->deserialize($json, DexListItem::class.'[]', 'json');
    }
}
