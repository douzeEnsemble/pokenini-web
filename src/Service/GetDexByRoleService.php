<?php

declare(strict_types=1);

namespace App\Service;

use App\Security\User;
use App\Service\Back\GetDexService;
use Symfony\Bundle\SecurityBundle\Security;

class GetDexByRoleService
{
    public function __construct(
        private readonly GetDexService $getDexService,
        private Security $security,
    ) {}

    /**
     * @return string[][]
     */
    public function getUserDex(): array
    {
        /** @var ?User $user */
        $user = $this->security->getUser();

        if (!$user) {
            return [];
        }

        return $user->isAnAdmin()
            ? $this->getDexService->getWithUnreleasedAndPremium()
            : $this->getDexService->getWithPremium();
    }
}
