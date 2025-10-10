<?php

declare(strict_types=1);

namespace App\DTO;

class UserInfo
{
    /**
     * @param array<int, string> $roles
     */
    public function __construct(
        private readonly string $id,
        private readonly string $provider,
        private readonly string $profile,
        private readonly array $roles,
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
}
