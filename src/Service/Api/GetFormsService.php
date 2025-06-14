<?php

declare(strict_types=1);

namespace App\Service\Api;

use App\Cache\KeyMaker;
use App\Utils\JsonDecoder;

class GetFormsService extends AbstractApiService
{
    /**
     * @return string[][]
     */
    public function getFormsCategory(): array
    {
        return $this->getFormsByType(
            'category',
            KeyMaker::getFormsCategoryKey(),
        );
    }

    /**
     * @return string[][]
     */
    public function getFormsRegional(): array
    {
        return $this->getFormsByType(
            'regional',
            KeyMaker::getFormsRegionalKey(),
        );
    }

    /**
     * @return string[][]
     */
    public function getFormsSpecial(): array
    {
        return $this->getFormsByType(
            'special',
            KeyMaker::getFormsSpecialKey(),
        );
    }

    /**
     * @return string[][]
     */
    public function getFormsVariant(): array
    {
        return $this->getFormsByType(
            'variant',
            KeyMaker::getFormsVariantKey(),
        );
    }

    /**
     * @return string[][]
     */
    private function getFormsByType(string $type, string $key): array
    {
        /** @var string $json */
        $json = $this->cache->get($key, function () use ($type) {
            return $this->requestContent(
                'GET',
                "/forms/{$type}",
            );
        });

        /** @var string[][] */
        return JsonDecoder::decode($json);
    }
}
