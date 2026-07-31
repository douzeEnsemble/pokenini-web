<?php

declare(strict_types=1);

namespace App\Service\Back;

use App\ResponseObject\Common\PokemonCreditRow;

class GetCreditsService extends AbstractBackService
{
    /**
     * @return PokemonCreditRow[]
     */
    public function get(): array
    {
        $json = $this->requestContent(
            'GET',
            '/credits'
        );

        /** @var PokemonCreditRow[] */
        return $this->serializer->deserialize($json, PokemonCreditRow::class.'[]', 'json');
    }
}
