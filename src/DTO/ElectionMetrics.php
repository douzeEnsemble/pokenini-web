<?php

declare(strict_types=1);

namespace App\DTO;

use Symfony\Component\OptionsResolver\OptionsResolver;

final class ElectionMetrics
{
    private function __construct(
        public readonly ElectionMetricsCounts $viewCount,
        public readonly ElectionMetricsCounts $winCount,
        public readonly ElectionMetricsCompletion $completion,
        public readonly int $dexTotalCount,
        public readonly int $roundCount,
        public readonly float $winnerAverage,
        public readonly int $totalRoundCount,
    ) {}

    public function getViewCount(): ElectionMetricsCounts
    {
        return $this->viewCount;
    }

    public function getWinCount(): ElectionMetricsCounts
    {
        return $this->winCount;
    }

    public function getCompletion(): ElectionMetricsCompletion
    {
        return $this->completion;
    }

    public function getDexTotalCount(): int
    {
        return $this->dexTotalCount;
    }

    public function getRoundCount(): int
    {
        return $this->roundCount;
    }

    public function getWinnerAverage(): float
    {
        return $this->winnerAverage;
    }

    public function getTotalRoundCount(): int
    {
        return $this->totalRoundCount;
    }

    /**
     * @param array{
     *   view_count: array{sum: int, max: int},
     *   win_count: array{sum: int, max: int},
     *   completion: array{under_max_count: int, at_max_count: int},
     *   dex_total_count: int,
     *   round_count: int,
     *   winner_average: float|int,
     *   total_round_count: int
     * } $data
     */
    public static function createFromArray(array $data): self
    {
        $resolver = new OptionsResolver();
        $resolver->setRequired(['view_count', 'win_count', 'completion', 'dex_total_count', 'round_count', 'winner_average', 'total_round_count']);
        $resolver->setAllowedTypes('view_count', 'array');
        $resolver->setAllowedTypes('win_count', 'array');
        $resolver->setAllowedTypes('completion', 'array');
        $resolver->setAllowedTypes('dex_total_count', 'int');
        $resolver->setAllowedTypes('round_count', 'int');
        $resolver->setAllowedTypes('winner_average', ['int', 'float']);
        $resolver->setAllowedTypes('total_round_count', 'int');

        /** @var array{view_count: array{sum: int, max: int}, win_count: array{sum: int, max: int}, completion: array{under_max_count: int, at_max_count: int}, dex_total_count: int, round_count: int, winner_average: float|int, total_round_count: int} $resolved */
        $resolved = $resolver->resolve($data);

        return new self(
            new ElectionMetricsCounts(self::int($resolved['view_count'], 'sum'), self::int($resolved['view_count'], 'max')),
            new ElectionMetricsCounts(self::int($resolved['win_count'], 'sum'), self::int($resolved['win_count'], 'max')),
            new ElectionMetricsCompletion(self::int($resolved['completion'], 'under_max_count'), self::int($resolved['completion'], 'at_max_count')),
            $resolved['dex_total_count'],
            $resolved['round_count'],
            (float) $resolved['winner_average'],
            $resolved['total_round_count'],
        );
    }

    /**
     * @param array<string, mixed> $sub
     */
    private static function int(array $sub, string $key): int
    {
        if (!isset($sub[$key]) || !is_int($sub[$key])) {
            throw new \InvalidArgumentException(sprintf('Missing or invalid metrics key "%s".', $key));
        }

        return $sub[$key];
    }
}
