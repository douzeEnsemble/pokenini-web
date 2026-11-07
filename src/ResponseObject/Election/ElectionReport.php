<?php

declare(strict_types=1);

namespace App\ResponseObject\Election;

use Symfony\Component\Serializer\Attribute\SerializedName;

final class ElectionReport
{
    /**
     * @param TopPokemon[] $top
     */
    public function __construct(
        #[SerializedName('metrics')]
        private readonly ElectionReportMetrics $metrics,
        #[SerializedName('top')]
        private readonly array $top = [],
    ) {}

    public function getMetrics(): ElectionReportMetrics
    {
        return $this->metrics;
    }

    /**
     * @return TopPokemon[]
     */
    public function getTop(): array
    {
        return $this->top;
    }

    /**
     * The trainer's favourite so far: the first of the top, if any.
     */
    public function getFavourite(): ?TopPokemon
    {
        return $this->top[0] ?? null;
    }
}
