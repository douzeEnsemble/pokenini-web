<?php

declare(strict_types=1);

namespace App\Service\Back;

use App\Utils\JsonDecoder;

class GetElectionDexService extends AbstractBackService
{
    /**
     * @return string[][]
     */
    public function get(): array
    {
        return $this->getDexWithParam([]);
    }

    /**
     * @return string[][]
     */
    public function getWithPremium(): array
    {
        return $this->getDexWithParam([
            'include_premium_dex' => '1',
        ]);
    }

    /**
     * @return string[][]
     */
    public function getWithUnreleasedAndPremium(): array
    {
        return $this->getDexWithParam([
            'include_unreleased_dex' => '1',
            'include_premium_dex' => '1',
        ]);
    }

    /**
     * @param string[] $queryParams
     *
     * @return string[][]
     */
    private function getDexWithParam(array $queryParams = []): array
    {
        $urlQueryParams = http_build_query($queryParams);

        $json = $this->requestContent(
            'GET',
            '/dex/can_hold_election'.($urlQueryParams ? '?'.$urlQueryParams : ''),
        );

        /** @var string[][] */
        return JsonDecoder::decode($json);
    }
}
