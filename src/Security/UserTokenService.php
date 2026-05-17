<?php

declare(strict_types=1);

namespace App\Security;

use App\Exception\NoLoggedUserException;
use Symfony\Bundle\SecurityBundle\Security;

final class UserTokenService implements UserTokenServiceInterface
{
    public function __construct(
        private readonly Security $security
    ) {}

    #[\Override]
    public function getLoggedUser(): User
    {
        /** @var null|User $user */
        $user = $this->security->getUser();

        if (null === $user) {
            throw new NoLoggedUserException('No user logged');
        }

        return $user;
    }

    #[\Override]
    public function getLoggedUserId(): string
    {
        $user = $this->getLoggedUser();

        return sha1($user->getUserIdentifier());
    }

    #[\Override]
    public function compare(string $identifier): bool
    {
        try {
            $loggedUserId = $this->getLoggedUserId();
        } catch (NoLoggedUserException) {
            return false;
        }

        return $loggedUserId === $identifier;
    }
}
