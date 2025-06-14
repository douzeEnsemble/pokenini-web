<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security;

use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
abstract class AbstractAuthenticatorTesting extends TestCase
{
    use AuthenticatorSupportTestTrait;
    use AuthenticatorAuthenticateClosedTestTrait;
    use AuthenticatorAuthenticateOpenedTestTrait;
    use AuthenticatorOnAuthentificationTestTrait;

    abstract protected function getAuthenticatorClassName(): string;

    abstract protected function getAuthenticatorProviderCode(): string;

    abstract protected function getAuthenticatorProviderName(): string;
}
