<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security;

use App\Security\PassageAuthenticator;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * @internal
 */
#[CoversClass(PassageAuthenticator::class)]
class PassageAuthenticatorTest extends AbstractAuthenticatorTesting
{
    #[\Override]
    protected function getAuthenticatorClassName(): string
    {
        return PassageAuthenticator::class;
    }

    #[\Override]
    protected function getAuthenticatorProviderCode(): string
    {
        return 'passage';
    }

    #[\Override]
    protected function getAuthenticatorProviderName(): string
    {
        return 'Passage';
    }
}
