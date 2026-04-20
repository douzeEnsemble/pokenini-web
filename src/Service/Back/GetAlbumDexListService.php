<?php

declare(strict_types=1);

namespace App\Service\Back;

use App\Utils\JsonDecoder;

class GetAlbumDexListService extends AbstractBackService
{
    /**
     * @return string[][]
     */
    public function get(?string $trainerId = null): array
    {
        /** @psalm-suppress RiskyTruthyFalsyComparison */
        $urlQueryParams = !empty($trainerId) ? "?trainer_id={$trainerId}" : '';

        $json = $this->requestContent(
            'GET',
            "/album/dex{$urlQueryParams}",
        );

        /** @var string[][] */
        return JsonDecoder::decode($json);
    }
}
