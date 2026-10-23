<?php

declare(strict_types=1);

namespace App\DTO;

/**
 * The page a trainer lands on when opening Pokénini, chosen in their trainer space.
 */
final class HomePage
{
    public const string HUB = 'hub';

    /**
     * The route of each page a trainer can choose.
     */
    public const array ROUTES = [
        self::HUB => 'app_home_hub',
        'album' => 'app_albumdexlist_index',
        'election' => 'app_electiondexlist_index',
        'game' => 'app_game_index',
    ];

    public readonly string $value;

    public function __construct(string $value)
    {
        // Anything unknown lands on the hub
        $this->value = self::isValid($value) ? $value : self::HUB;
    }

    public static function isValid(string $value): bool
    {
        return \array_key_exists($value, self::ROUTES);
    }

    public function isHub(): bool
    {
        return self::HUB === $this->value;
    }

    public function getRoute(): string
    {
        return self::ROUTES[$this->value];
    }
}
