<?php

declare(strict_types=1);

namespace App\DTO;

/**
 * The last round answered in a game, kept in the session so its answer page can be reloaded.
 */
final class GameAnsweredRound
{
    public function __construct(
        public readonly string $gameId,
        public readonly int $position,
        public readonly object $round,
        public readonly object $answer,
    ) {}

    public function isFor(string $gameId, int $position): bool
    {
        return $this->gameId === $gameId && $this->position === $position;
    }
}
