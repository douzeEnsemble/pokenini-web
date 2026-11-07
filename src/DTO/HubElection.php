<?php

declare(strict_types=1);

namespace App\DTO;

use App\ResponseObject\Election\ElectionDexListItem;

/**
 * One election shown on the hub, with how far the trainer went in it.
 */
final class HubElection
{
    public const string IN_PROGRESS = 'in_progress';
    public const string NOT_STARTED = 'not_started';
    public const string FINISHED = 'finished';

    public readonly string $state;

    public function __construct(
        public readonly ElectionDexListItem $dex,
    ) {
        $this->state = self::getState($dex);
    }

    public function isInProgress(): bool
    {
        return self::IN_PROGRESS === $this->state;
    }

    private static function getState(ElectionDexListItem $dex): string
    {
        $metrics = $dex->getReport()?->getMetrics();

        if (null === $metrics || 0 === $metrics->getRoundCount()) {
            return self::NOT_STARTED;
        }

        $completion = $metrics->getCompletion();

        // Every Pokémon reached the maximum of views: nothing is left to vote
        if (0 === $completion->getUnderMaxCount() && $completion->getAtMaxCount() === $metrics->getDexTotalCount()) {
            return self::FINISHED;
        }

        return self::IN_PROGRESS;
    }
}
