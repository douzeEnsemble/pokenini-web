<?php

declare(strict_types=1);

namespace App\Service\Back;

use App\ResponseObject\Common\PokemonCreditRow;

class GetCreditsService extends AbstractBackService
{
    /**
     * @param string $locale the language of the names of the Pokémon
     *
     * @return PokemonCreditRow[]
     */
    public function get(string $locale): array
    {
        $json = $this->requestContent(
            'GET',
            '/credits',
            ['query' => ['lang' => $locale]],
        );

        /** @var PokemonCreditRow[] */
        return $this->serializer->deserialize($json, PokemonCreditRow::class.'[]', 'json');
    }
}
