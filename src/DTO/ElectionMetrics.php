<?php

declare(strict_types=1);

namespace App\DTO;

use Symfony\Component\OptionsResolver\OptionsResolver;

final class ElectionMetrics
{
    /**
     * @SuppressWarnings("PHPMD.ExcessiveParameterList")
     */
    private function __construct(
        public readonly int $viewCountSum,
        public readonly int $winCountSum,
        public readonly int $viewCountMax,
        public readonly int $winCountMax,
        public readonly int $underMaxViewCount,
        public readonly int $maxViewCount,
        public readonly int $dexTotalCount,
        public readonly int $roundCount,
        public readonly float $winnerAverage,
        public readonly int $totalRoundCount,
    ) {}

    /**
     * @param array{
     *  view_count: array{sum: int, max: int},
     *  win_count: array{sum: int, max: int},
     *  completion: array{under_max_count: int, at_max_count: int},
     *  dex_total_count: int,
     *  round_count: int,
     *  winner_average: float,
     *  total_round_count: int,
     * } $values
     */
    public static function createFromArray(array $values): self
    {
        $resolver = new OptionsResolver();
        self::configureOptions($resolver);

        /**
         * @var array{
         *  view_count: array<array-key, int>,
         *  win_count: array<array-key, int>,
         *  completion: array<array-key, int>,
         *  dex_total_count: int,
         *  round_count: int,
         *  winner_average: float,
         *  total_round_count: int,
         * }
         */
        $options = $resolver->resolve($values);

        $viewCount = self::resolveSumMax($options['view_count']);
        $winCount = self::resolveSumMax($options['win_count']);
        $completion = self::resolveCompletion($options['completion']);

        return new self(
            $viewCount['sum'],
            $winCount['sum'],
            $viewCount['max'],
            $winCount['max'],
            $completion['under_max_count'],
            $completion['at_max_count'],
            $options['dex_total_count'],
            $options['round_count'],
            $options['winner_average'],
            $options['total_round_count'],
        );
    }

    private static function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired('view_count');
        $resolver->setAllowedTypes('view_count', 'array');

        $resolver->setRequired('win_count');
        $resolver->setAllowedTypes('win_count', 'array');

        $resolver->setRequired('completion');
        $resolver->setAllowedTypes('completion', 'array');

        $resolver->setRequired('dex_total_count');
        $resolver->setAllowedTypes('dex_total_count', 'int');

        $resolver->setRequired('round_count');
        $resolver->setAllowedTypes('round_count', 'int');

        $resolver->setRequired('winner_average');
        $resolver->setAllowedTypes('winner_average', ['float']);

        $resolver->setRequired('total_round_count');
        $resolver->setAllowedTypes('total_round_count', 'int');
    }

    /**
     * @param array<array-key, int> $value
     *
     * @return array{sum: int, max: int}
     */
    private static function resolveSumMax(array $value): array
    {
        $resolver = new OptionsResolver();

        $resolver->setRequired('sum');
        $resolver->setAllowedTypes('sum', 'int');

        $resolver->setRequired('max');
        $resolver->setAllowedTypes('max', 'int');

        /** @var array{sum: int, max: int} */
        return $resolver->resolve($value);
    }

    /**
     * @param array<array-key, int> $value
     *
     * @return array{under_max_count: int, at_max_count: int}
     */
    private static function resolveCompletion(array $value): array
    {
        $resolver = new OptionsResolver();

        $resolver->setRequired('under_max_count');
        $resolver->setAllowedTypes('under_max_count', 'int');

        $resolver->setRequired('at_max_count');
        $resolver->setAllowedTypes('at_max_count', 'int');

        /** @var array{under_max_count: int, at_max_count: int} */
        return $resolver->resolve($value);
    }
}
