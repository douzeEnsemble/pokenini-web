<?php

declare(strict_types=1);

namespace App\Service\Back;

use App\ResponseObject\Album\TrainerDexLink;

class TrainerDexLinkService extends AbstractBackService
{
    /**
     * @return TrainerDexLink[]
     */
    public function list(string $dexSlug): array
    {
        $json = $this->requestContent('GET', "/album_link/{$dexSlug}");

        /** @var TrainerDexLink[] */
        return $this->serializer->deserialize($json, TrainerDexLink::class.'[]', 'json');
    }

    public function create(string $dexSlug, string $body): void
    {
        $this->request('POST', "/album_link/{$dexSlug}", ['body' => $body]);
    }

    public function delete(string $linkId): void
    {
        $this->request('DELETE', "/album_link/{$linkId}");
    }
}
