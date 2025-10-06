<?php

declare(strict_types=1);

namespace App\Service\Back;

use Symfony\Component\HttpFoundation\Request;

class ModifyAlbumService extends AbstractBackService
{
    public function modify(
        string $method,
        string $dexSlug,
        string $pokemonSlug,
        string $catchStateSlug,
    ): void {
        if (!in_array($method, [Request::METHOD_PATCH, Request::METHOD_PUT], true)) {
            throw new \InvalidArgumentException();
        }

        $this->request(
            $method,
            "/album/{$dexSlug}/{$pokemonSlug}",
            [
                'body' => $catchStateSlug,
            ]
        );
    }
}
