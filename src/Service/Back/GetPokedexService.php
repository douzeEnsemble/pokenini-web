<?php

declare(strict_types=1);

namespace App\Service\Back;

use App\ResponseObject\Album\Album;

class GetPokedexService extends AbstractBackService
{
    /**
     * @param string[]|string[][] $filters
     * @param null|string         $locale  the language of the names; the API gives both languages without it
     */
    public function get(
        string $dexSlug,
        array $filters = [],
        ?string $locale = null,
    ): Album {
        $url = "/album/{$dexSlug}";

        return $this->getData($url, $filters, $locale);
    }

    /**
     * @param string[]|string[][] $filters
     * @param null|string         $locale  the language of the names; the API gives both languages without it
     */
    public function getWithTrainerId(
        string $trainerId,
        string $dexSlug,
        array $filters = [],
        ?string $locale = null,
    ): Album {
        $url = "/album/{$dexSlug}";

        $filters['trainer_id'] = $trainerId;

        return $this->getData($url, $filters, $locale);
    }

    /**
     * @param string[]|string[][] $filters
     */
    private function getData(string $url, array $filters, ?string $locale): Album
    {
        if (null !== $locale) {
            $filters['lang'] = $locale;
        }

        $json = $this->requestContent(
            'GET',
            $url,
            [
                'query' => $filters,
            ],
        );

        return $this->serializer->deserialize($json, Album::class, 'json');
    }
}
