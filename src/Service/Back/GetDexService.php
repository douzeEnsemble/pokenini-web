<?php

declare(strict_types=1);

namespace App\Service\Back;

use App\Utils\JsonDecoder;

class GetDexService extends AbstractBackService
{
    /**
     * @return string[][]
     */
    public function get(?string $trainerId = null): array
    {
        return $this->getDexWithParam([
            'trainer_id' => $trainerId,
        ]);
    }

    /**
     * @return string[][]
     */
    public function getWithUnreleased(?string $trainerId = null): array
    {
        return $this->getDexWithParam([
            'trainer_id' => $trainerId,
            'include_unreleased_dex' => '1',
        ]);
    }

    /**
     * @return string[][]
     */
    public function getWithPremium(?string $trainerId = null): array
    {
        return $this->getDexWithParam([
            'trainer_id' => $trainerId,
            'include_premium_dex' => '1',
        ]);
    }

    /**
     * @return string[][]
     */
    public function getWithUnreleasedAndPremium(?string $trainerId = null): array
    {
        return $this->getDexWithParam([
            'trainer_id' => $trainerId,
            'include_unreleased_dex' => '1',
            'include_premium_dex' => '1',
        ]);
    }

    /**
     * @param array<string, null|string> $queryParams
     *
     * @return string[][]
     */
    private function getDexWithParam(array $queryParams = []): array
    {
        $urlQueryParams = http_build_query($queryParams);

        $json = $this->requestContent(
            'GET',
            '/dex/list'.($urlQueryParams ? '?'.$urlQueryParams : ''),
        );

        /** @var string[][] */
        return JsonDecoder::decode($json);
    }
}
