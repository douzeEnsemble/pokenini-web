<?php

declare(strict_types=1);

namespace App\Service\Back;

use App\ResponseObject\Common\PokemonCredit;

class GetCreditsService extends AbstractBackService
{
    /**
     * @return PokemonCredit[]
     */
    public function get(): array
    {
        $json = $this->requestContent(
            'GET',
            '/credits'
        );

        /** @var PokemonCredit[] */
        return $this->serializer->deserialize($json, PokemonCredit::class.'[]', 'json');
    }
}
