<?php

declare(strict_types=1);

namespace App\Service\Api;

use Symfony\Component\HttpFoundation\Request;

class ModifyAlbumService extends AbstractApiService
{
    public function modify(
        string $method,
        string $dexSlug,
        string $pokemonSlug,
        string $catchStateSlug,
        string $trainerId
    ): void {
        if (!in_array($method, [Request::METHOD_PATCH, Request::METHOD_PUT], true)) {
            throw new \InvalidArgumentException();
        }

        $this->request(
            $method,
            "/album/{$trainerId}/{$dexSlug}/{$pokemonSlug}",
            [
                'body' => $catchStateSlug,
            ]
        );
    }
}
