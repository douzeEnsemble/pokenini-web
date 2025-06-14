<?php

declare(strict_types=1);

namespace App\Service\Api;

class ModifyDexService extends AbstractApiService
{
    public function modify(
        string $dexSlug,
        string $data,
        string $trainerId
    ): void {
        $this->request(
            'PUT',
            "/dex/{$trainerId}/{$dexSlug}",
            [
                'body' => $data,
            ]
        );
    }
}
