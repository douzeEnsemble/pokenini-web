<?php

declare(strict_types=1);

namespace App\DTO;

use App\Helper\TotalRoundCountHelper;
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
     * @param float[]|int[] $values
     */
    public function __construct(array $values, int $perViewCount)
    {
        $resolver = new OptionsResolver();
        $this->configureOptions($resolver);

        $options = $resolver->resolve($values);

        $this->viewCountSum = $options['view_count_sum'];
        $this->winCountSum = $options['win_count_sum'];
        $this->viewCountMax = $options['view_count_max'];
        $this->winCountMax = $options['win_count_max'];
        $this->underMaxViewCount = $options['under_max_view_count'];
        $this->maxViewCount = $options['max_view_count'];
        $this->dexTotalCount = $options['dex_total_count'];

        $this->roundCount = (int) round($this->viewCountSum / $perViewCount);
        $this->winnerAverage = 4.0;
        if (0 !== $this->roundCount) {
            $this->winnerAverage = round($this->winCountSum / $this->roundCount, 2);
        }

        $this->totalRoundCount = TotalRoundCountHelper::calculate(
            $this->dexTotalCount,
            $perViewCount,
            $this->winnerAverage,
        );
    }

    private function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefault('view_count_sum', 0);
        $resolver->setAllowedTypes('view_count_sum', 'int');

        $resolver->setDefault('win_count_sum', 0);
        $resolver->setAllowedTypes('win_count_sum', 'int');

        $resolver->setDefault('view_count_max', 0);
        $resolver->setAllowedTypes('view_count_max', 'int');

        $resolver->setDefault('win_count_max', 0);
        $resolver->setAllowedTypes('win_count_max', 'int');

        $resolver->setDefault('under_max_view_count', 0);
        $resolver->setAllowedTypes('under_max_view_count', 'int');

        $resolver->setDefault('max_view_count', 0);
        $resolver->setAllowedTypes('max_view_count', 'int');

        $resolver->setDefault('dex_total_count', 0);
        $resolver->setAllowedTypes('dex_total_count', 'int');
    }
}
