<?php

namespace App\Service;

use App\Exception\ModifyFailedException;
use App\Service\Back\ModifyDexService;
use Symfony\Contracts\HttpClient\Exception\HttpExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class ModifyTrainerDexService
{
    public function __construct(
        private readonly ModifyDexService $modifyDexService,
    ) {}

    public function modifyDex(string $dexSlug, string $content): void
    {
        try {
            $this->modifyDexService->modify(
                $dexSlug,
                $content,
            );
        } catch (HttpExceptionInterface|TransportExceptionInterface $e) {
            throw new ModifyFailedException();
        }
    }
}
