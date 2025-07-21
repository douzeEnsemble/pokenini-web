<?php

namespace App\Service;

use App\Exception\ModifyFailedException;
use App\Security\UserTokenService;
use App\Service\Back\ModifyDexService;
use Symfony\Contracts\HttpClient\Exception\HttpExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class ModifyTrainerDexService
{
    public function __construct(
        private readonly UserTokenService $userTokenService,
        private readonly ModifyDexService $modifyDexService,
    ) {}

    public function modifyDex(string $dexSlug, string $content): void
    {
        $trainerId = $this->userTokenService->getLoggedUserToken();

        try {
            $this->modifyDexService->modify(
                $dexSlug,
                $content,
                $trainerId
            );
        } catch (HttpExceptionInterface|TransportExceptionInterface $e) {
            throw new ModifyFailedException();
        }
    }
}
