<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security;

use App\Security\GoogleAuthenticator;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * @internal
 */
#[CoversClass(GoogleAuthenticator::class)]
class GoogleAuthenticatorTest extends AbstractAuthenticatorTesting
{
    #[\Override]
    protected function getAuthenticatorClassName(): string
    {
        return GoogleAuthenticator::class;
    }

    #[\Override]
    protected function getAuthenticatorProviderCode(): string
    {
        return 'google';
    }

    #[\Override]
    protected function getAuthenticatorProviderName(): string
    {
        return 'Google';
    }
}
