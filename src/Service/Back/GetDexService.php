<?php

declare(strict_types=1);

namespace App\Service\Back;

use App\Utils\JsonDecoder;

class GetDexService extends AbstractBackService
{
    /**
     * @return string[][]
     */
    public function get(string $trainerId): array
    {
        return $this->getDexWithParam($trainerId, []);
    }

    /**
     * @return string[][]
     */
    public function getWithUnreleased(string $trainerId): array
    {
        return $this->getDexWithParam($trainerId, [
            'include_unreleased_dex' => '1',
        ]);
    }

    /**
     * @return string[][]
     */
    public function getWithPremium(string $trainerId): array
    {
        return $this->getDexWithParam($trainerId, [
            'include_premium_dex' => '1',
        ]);
    }

    /**
     * @return string[][]
     */
    public function getWithUnreleasedAndPremium(string $trainerId): array
    {
        return $this->getDexWithParam($trainerId, [
            'include_unreleased_dex' => '1',
            'include_premium_dex' => '1',
        ]);
    }

    /**
     * @param string[] $queryParams
     *
     * @return string[][]
     */
    private function getDexWithParam(string $trainerId, array $queryParams = []): array
    {
        $urlQueryParams = http_build_query($queryParams);

        $json = $this->requestContent(
            'GET',
            "/dex/{$trainerId}/list".($urlQueryParams ? '?'.$urlQueryParams : ''),
        );

        /** @var string[][] */
        return JsonDecoder::decode($json);
    }
}
