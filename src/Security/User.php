<?php

declare(strict_types=1);

namespace App\Security;

use Symfony\Component\Security\Core\User\UserInterface;

class User implements UserInterface
{
    /** @var string[] */
    private array $roles = ['ROLE_USER'];

    public function __construct(
        private readonly string $identifier,
        private readonly string $providerName,
    ) {}

    #[\Override]
    public function getRoles(): array
    {
        return $this->roles;
    }

    public function addAdminRole(): void
    {
        $this->roles[] = 'ROLE_ADMIN';

        $this->roles = array_unique($this->roles);
    }

    public function addTrainerRole(): void
    {
        $this->roles[] = 'ROLE_TRAINER';

        $this->roles = array_unique($this->roles);
    }

    public function addCollectorRole(): void
    {
        $this->roles[] = 'ROLE_COLLECTOR';

        $this->roles = array_unique($this->roles);
    }

    // @codeCoverageIgnoreStart
    #[\Override]
    public function eraseCredentials(): void
    {
        // nothing sensitive
    }
    // @codeCoverageIgnoreEnd

    #[\Override]
    public function getUserIdentifier(): string
    {
        return $this->identifier;
    }

    public function getId(): string
    {
        return $this->getUserIdentifier();
    }

    public function getProviderName(): string
    {
        return $this->providerName;
    }

    public function isATrainer(): bool
    {
        return in_array('ROLE_TRAINER', $this->getRoles());
    }

    public function isACollector(): bool
    {
        return in_array('ROLE_COLLECTOR', $this->getRoles());
    }

    public function isAnAdmin(): bool
    {
        return in_array('ROLE_ADMIN', $this->getRoles());
    }

    public function getProfile(): string
    {
        if ($this->isAnAdmin()) {
            return 'admin';
        }

        if ($this->isACollector()) {
            return 'collector';
        }

        if ($this->isATrainer()) {
            return 'trainer';
        }

        return 'user';
    }
}
