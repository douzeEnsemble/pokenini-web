<?php

declare(strict_types=1);

namespace App\DTO;

use Symfony\Component\OptionsResolver\OptionsResolver;

final class ElectionMetrics
{
    public int $viewCountSum;

    public int $winCountSum;

    public int $viewCountMax;

    public int $winCountMax;

    public int $underMaxViewCount;

    public int $maxViewCount;

    public int $dexTotalCount;

    public int $roundCount;

    public float $winnerAverage;

    public int $totalRoundCount;

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
    public function __construct(array $values)
    {
        $resolver = new OptionsResolver();
        $this->configureOptions($resolver);

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

        $this->viewCountSum = $options['view_count_sum'];
        $this->winCountSum = $options['win_count_sum'];
        $this->viewCountMax = $options['view_count_max'];
        $this->winCountMax = $options['win_count_max'];
        $this->underMaxViewCount = $options['under_max_view_count'];
        $this->maxViewCount = $options['max_view_count'];
        $this->dexTotalCount = $options['dex_total_count'];
        $this->roundCount = $options['round_count'];
        $this->winnerAverage = $options['winner_average'];
        $this->totalRoundCount = $options['total_round_count'];
    }

    private function configureOptions(OptionsResolver $resolver): void
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
