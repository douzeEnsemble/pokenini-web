<?php

declare(strict_types=1);

namespace App\Security;

use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

/**
 * @implements UserProviderInterface<UserInterface>
 */
final class UserProvider implements UserProviderInterface, PasswordUpgraderInterface
{
    /**
     * @SuppressWarnings("PHPMD.UnusedFormalParameter")
     */
    #[\Override]
    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        throw new \RuntimeException('Not use in this project');
    }

    #[\Override]
    /**
     * @return User
     */
    public function refreshUser(UserInterface $user): UserInterface
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Invalid user class "%s".', get_class($user)));
        }

        // The user only lives in the session: the pokenini-back session token it carries is
        // valid for SESSION_TTL, like the session itself. The provider (Google/Discord) access
        // token is not used after login, so its own, much shorter, expiry must not log the user out.
        return $user;
    }

    #[\Override]
    public function supportsClass(string $class): bool
    {
        return User::class === $class;
    }

    /**
     * @SuppressWarnings("PHPMD.UnusedFormalParameter")
     */
    #[\Override]
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        // nothing
    }
}
