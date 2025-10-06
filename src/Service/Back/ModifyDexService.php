<?php

declare(strict_types=1);

namespace App\Service\Back;

class ModifyDexService extends AbstractBackService
{
    public function modify(
        string $dexSlug,
        string $data,
    ): void {
        $this->request(
            'PUT',
            "/dex/{$dexSlug}",
            [
                'body' => $data,
            ]
        );
    }
}
