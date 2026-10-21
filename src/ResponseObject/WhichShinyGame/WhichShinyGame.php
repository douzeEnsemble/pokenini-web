<?php

declare(strict_types=1);

namespace App\ResponseObject\WhichShinyGame;

use Symfony\Component\Serializer\Attribute\SerializedName;

/**
 * @SuppressWarnings("PHPMD.ExcessiveParameterList")
 */
final class WhichShinyGame
{
    public function __construct(
        #[SerializedName('id')]
        private readonly string $id,
        #[SerializedName('dex')]
        private readonly WhichShinyGameDexSummary $dex,
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
    ) {}

    public function getId(): string
    {
        return $this->id;
    }

    public function getDex(): WhichShinyGameDexSummary
    {
        return $this->dex;
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
}
