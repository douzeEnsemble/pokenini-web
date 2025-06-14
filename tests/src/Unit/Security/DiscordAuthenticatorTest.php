<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security;

use App\Security\DiscordAuthenticator;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * @internal
 */
#[CoversClass(DiscordAuthenticator::class)]
class DiscordAuthenticatorTest extends AbstractAuthenticatorTesting
{
    #[\Override]
    protected function getAuthenticatorClassName(): string
    {
        return DiscordAuthenticator::class;
    }

    #[\Override]
    protected function getAuthenticatorProviderCode(): string
    {
        return 'discord';
    }

    #[\Override]
    protected function getAuthenticatorProviderName(): string
    {
        return 'Discord';
    }
}
