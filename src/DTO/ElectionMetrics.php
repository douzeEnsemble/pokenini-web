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
     *  view_count_sum: int,
     *  win_count_sum: int,
     *  view_count_max: int,
     *  win_count_max: int,
     *  under_max_view_count: int,
     *  max_view_count: int,
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
         *  view_count_sum: int,
         *  win_count_sum: int,
         *  view_count_max: int,
         *  win_count_max: int,
         *  under_max_view_count: int,
         *  max_view_count: int,
         *  dex_total_count: int,
         *  round_count: int,
         *  winner_average: float,
         *  total_round_count: int,
         * }
         */
        $options = $resolver->resolve($values);

        return new self(
            $options['view_count_sum'],
            $options['win_count_sum'],
            $options['view_count_max'],
            $options['win_count_max'],
            $options['under_max_view_count'],
            $options['max_view_count'],
            $options['dex_total_count'],
            $options['round_count'],
            $options['winner_average'],
            $options['total_round_count'],
        );
    }

    private static function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired('view_count_sum');
        $resolver->setAllowedTypes('view_count_sum', 'int');

        $resolver->setRequired('win_count_sum');
        $resolver->setAllowedTypes('win_count_sum', 'int');

        $resolver->setRequired('view_count_max');
        $resolver->setAllowedTypes('view_count_max', 'int');

        $resolver->setRequired('win_count_max');
        $resolver->setAllowedTypes('win_count_max', 'int');

        $resolver->setRequired('under_max_view_count');
        $resolver->setAllowedTypes('under_max_view_count', 'int');

        $resolver->setRequired('max_view_count');
        $resolver->setAllowedTypes('max_view_count', 'int');

        $resolver->setRequired('dex_total_count');
        $resolver->setAllowedTypes('dex_total_count', 'int');

        $resolver->setRequired('round_count');
        $resolver->setAllowedTypes('round_count', 'int');

        $resolver->setRequired('winner_average');
        $resolver->setAllowedTypes('winner_average', ['float']);

        $resolver->setRequired('total_round_count');
        $resolver->setAllowedTypes('total_round_count', 'int');
    }
}
