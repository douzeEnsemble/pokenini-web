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

    public function getLoggedUserToken(): string
    {
        /** @var null|User $user */
        $user = $this->security->getUser();

        if (null === $user) {
            throw new NoLoggedUserException('No user logged');
        }

        return sha1($user->getUserIdentifier());
    }
}
