<?php

declare(strict_types=1);

namespace App\Security;

use App\Exception\NoLoggedUserException;
use Symfony\Component\Security\Core\Security;

class UserTokenService
{
    public function __construct(
        private readonly Security $security
    ) {
    }

    public function getLoggedUserToken(): string
    {
        /** @var User|null $user */
        $user = $this->security->getUser();

        if (null === $user) {
            throw new NoLoggedUserException('No user logged');
        }

        return sha1($user->getUserIdentifier());
    }
}
