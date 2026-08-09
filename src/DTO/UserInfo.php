<?php

declare(strict_types=1);

namespace App\DTO;

use Symfony\Component\Serializer\Attribute\SerializedName;

final class UserInfo
{
    /**
     * @param array<int, string> $roles
     */
    public function __construct(
        private readonly string $id,
        private readonly string $provider,
        private readonly string $profile,
        private readonly array $roles,
        #[SerializedName('session_token')]
        private readonly string $sessionToken,
    ) {}

    public function getId(): string
    {
        return $this->id;
    }

    public function getProvider(): string
    {
        return $this->provider;
    }

    public function getProfile(): string
    {
        return $this->profile;
    }

    /**
     * @return array<int, string>
     */
    public function getRoles(): array
    {
        return $this->roles;
    }

    public function getSessionToken(): string
    {
        return $this->sessionToken;
    }
}
