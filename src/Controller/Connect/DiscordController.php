<?php

declare(strict_types=1);

namespace App\Controller\Connect;

use Symfony\Component\Routing\Attribute\Route;

#[Route('/connect/dd')]
class DiscordController extends AbstractConnectController
{
    #[\Override]
    protected function getProviderName(): string
    {
        return 'discord';
    }

    #[\Override]
    protected function getScope(): string
    {
        return 'openid';
    }
}
