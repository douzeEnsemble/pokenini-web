<?php

declare(strict_types=1);

namespace App\Security;

class AmazonAuthenticator extends AbstractAuthenticator
{
    #[\Override]
    protected function getProviderCode(): string
    {
        return 'amazon';
    }

    #[\Override]
    protected function getProviderName(): string
    {
        return 'Amazon';
    }
}
