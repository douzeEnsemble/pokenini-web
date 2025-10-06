<?php

declare(strict_types=1);

namespace App\Service\Back;

use App\Utils\JsonDecoder;

class GetElectionTopService extends AbstractBackService
{
    /**
     * @return string[][]
     */
    public function getTop(
        string $dexSlug,
        string $electionSlug,
        int $count,
    ): array {
        /** @var string $json */
        $json = $this->requestContent(
            'GET',
            "/election/top?dex_slug={$dexSlug}&election_slug={$electionSlug}&count={$count}"
        );

        /** @var string[][] */
        return JsonDecoder::decode($json);
    }
}
