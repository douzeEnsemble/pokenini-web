<?php

declare(strict_types=1);

namespace App\Tests\Utils;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Storage\SessionStorageFactoryInterface;
use Symfony\Component\HttpFoundation\Session\Storage\SessionStorageInterface;

/**
 * The session storage of the test environment.
 *
 * The functional tests mock the session. A real server, like the one the browser tests call, needs the native
 * storage: it locks the session, so that parallel requests of the browser do not overwrite each other's data
 * (and lose the CSRF token the page has just generated).
 */
final class SwitchableSessionStorageFactory implements SessionStorageFactoryInterface
{
    public function __construct(
        private readonly SessionStorageFactoryInterface $native,
        private readonly SessionStorageFactoryInterface $mock,
        private readonly bool $useMock,
    ) {}

    #[\Override]
    public function createStorage(?Request $request): SessionStorageInterface
    {
        return $this->useMock ? $this->mock->createStorage($request) : $this->native->createStorage($request);
    }
}
