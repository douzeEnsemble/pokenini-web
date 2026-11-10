<?php

declare(strict_types=1);

namespace App\Service\Back;

use App\ResponseObject\Election\ElectionDexListItem;

class GetElectionDexListService extends AbstractBackService
{
    /**
     * @param null|string $locale the language of the names, null for both languages
     *
     * @return ElectionDexListItem[]
     */
    public function get(?string $locale = null): array
    {
        $json = $this->requestContent(
            'GET',
            '/election/dex',
            null === $locale ? [] : ['query' => ['lang' => $locale]],
        );

        /** @var ElectionDexListItem[] */
        return $this->serializer->deserialize($json, ElectionDexListItem::class.'[]', 'json');
    }
}
