<?php

declare(strict_types=1);

namespace App\ResponseObject\ColorGame;

use Symfony\Component\Serializer\Attribute\SerializedName;

/**
 * @SuppressWarnings("PHPMD.ExcessiveParameterList")
 */
final class ColorGame
{
    /**
     * @param list<bool> $results answered rounds results, in the rounds order
     */
    public function __construct(
        #[SerializedName('id')]
        private readonly string $id,
        #[SerializedName('dex')]
        private readonly ColorGameDexSummary $dex,
        #[SerializedName('is_shiny')]
        private readonly bool $isShiny,
        #[SerializedName('status')]
        private readonly string $status,
        #[SerializedName('round_count')]
        private readonly int $roundCount,
        #[SerializedName('answered_count')]
        private readonly int $answeredCount,
        #[SerializedName('correct_count')]
        private readonly int $correctCount,
        #[SerializedName('started_at')]
        private readonly string $startedAt,
        #[SerializedName('last_played_at')]
        private readonly ?string $lastPlayedAt,
        #[SerializedName('ended_at')]
        private readonly ?string $endedAt,
        #[SerializedName('results')]
        private readonly array $results = [],
    ) {}

    public function getId(): string
    {
        return $this->id;
    }

    public function getDex(): ColorGameDexSummary
    {
        return $this->dex;
    }

    public function isShiny(): bool
    {
        return $this->isShiny;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getRoundCount(): int
    {
        return $this->roundCount;
    }

    public function getAnsweredCount(): int
    {
        return $this->answeredCount;
    }

    public function getCorrectCount(): int
    {
        return $this->correctCount;
    }

    public function getStartedAt(): string
    {
        return $this->startedAt;
    }

    public function getLastPlayedAt(): ?string
    {
        return $this->lastPlayedAt;
    }

    public function getEndedAt(): ?string
    {
        return $this->endedAt;
    }

    /**
     * @return list<bool> answered rounds results, in the rounds order
     */
    public function getResults(): array
    {
        return $this->results;
    }
}
