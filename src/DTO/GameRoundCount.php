<?php

declare(strict_types=1);

namespace App\DTO;

/**
 * The number of rounds chosen by the player when a game starts.
 */
final class GameRoundCount
{
    /**
     * Null to play the whole dex.
     */
    public readonly ?int $value;

    public function __construct(string $value)
    {
        // Anything but a positive integer (empty, "all"…) plays the whole dex
        $this->value = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1], 'flags' => FILTER_NULL_ON_FAILURE]);
    }
}
