<?php

declare(strict_types=1);

namespace App\ResponseObject\Album;

use Symfony\Component\Serializer\Attribute\SerializedName;

class Album
{
    /**
     * @param Pokemon[] $pokemons
     */
    public function __construct(
        #[SerializedName('dex')]
        private readonly Dex $dex,
        #[SerializedName('pokemons')]
        private readonly array $pokemons,
        #[SerializedName('report')]
        private readonly Report $report,
        #[SerializedName('filtered_report')]
        private readonly Report $filteredReport,
    ) {}

    public function getDex(): Dex
    {
        return $this->dex;
    }

    /**
     * @return Pokemon[]
     */
    public function getPokemons(): array
    {
        return $this->pokemons;
    }

    public function getReport(): Report
    {
        return $this->report;
    }

    public function getFilteredReport(): Report
    {
        return $this->filteredReport;
    }
}
