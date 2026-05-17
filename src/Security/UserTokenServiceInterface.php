<?php

declare(strict_types=1);

namespace App\Security;

use App\Exception\NoLoggedUserException;

interface UserTokenServiceInterface
{
    /**
     * @throws NoLoggedUserException
     */
    public function getLoggedUser(): User;

    /**
     * @throws NoLoggedUserException
     */
    public function getLoggedUserId(): string;

    public function compare(string $identifier): bool;
}
