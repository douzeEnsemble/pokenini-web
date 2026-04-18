<?php

declare(strict_types=1);

namespace App\Controller\Connect;

use Symfony\Component\Routing\Attribute\Route;

#[Route('/connect/g')]
final class GoogleController extends AbstractConnectController
{
    #[\Override]
    protected function getProviderName(): string
    {
        return 'google';
    }

    #[\Override]
    protected function getScope(): string
    {
        return 'openid';
    }
}
