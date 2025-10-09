<?php

declare(strict_types=1);

namespace App\Security;

use App\Exception\NoLoggedUserException;
use Symfony\Bundle\SecurityBundle\Security;

class UserTokenService
{
    public function __construct(
        private readonly Security $security
    ) {}

    public function getLoggedUserId(): string
    {
        /** @var null|User $user */
        $user = $this->security->getUser();

        if (null === $user) {
            throw new NoLoggedUserException('No user logged');
        }

        return sha1($user->getUserIdentifier());
    }

    public function compare(string $identifier): bool
    {
        try {
            $loggedUserId = $this->getLoggedUserId();
        } catch (NoLoggedUserException $e) {
            return false;
        }

        return $loggedUserId === $identifier;
    }

    public function getLoggedUser(): User
    {
        /** @var null|User $user */
        $user = $this->security->getUser();

        if (null === $user) {
            throw new NoLoggedUserException('No user logged');
        }

        return $user;
    }
}
