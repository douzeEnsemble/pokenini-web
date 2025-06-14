<?php

declare(strict_types=1);

namespace App\Controller\Connect;

use Symfony\Component\Routing\Annotation\Route;

#[Route('/connect/az')]
class AmazonController extends AbstractConnectController
{
    #[\Override]
    protected function getProviderName(): string
    {
        return 'amazon';
    }

    #[\Override]
    protected function getScope(): string
    {
        return 'profile';
    }
}
