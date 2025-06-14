<?php

declare(strict_types=1);

namespace App\Controller\Connect;

use Symfony\Component\Routing\Annotation\Route;

#[Route('/connect/p')]
class PassageController extends AbstractConnectController
{
    #[\Override]
    protected function getProviderName(): string
    {
        return 'passage';
    }

    #[\Override]
    protected function getScope(): string
    {
        return 'openid';
    }
}
