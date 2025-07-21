<?php

declare(strict_types=1);

namespace App\DTO;

class UserInfo
{
    /**
     * @param string[] $roles
     */
    private function __construct(
        public readonly string $identifier,
        public readonly array $roles,
    ) {}

    /**
     * @param array<string|string[]> $data
     */
    public static function createFromArray(array $data): self
    {
        /** @var string */
        $identifier = $data['identifier'];

        /** @var string[] */
        $roles = $data['roles'] ?? [];

        return new self(
            $identifier,
            $roles,
        );
    }
}
