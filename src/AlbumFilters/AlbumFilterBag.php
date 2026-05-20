<?php

declare(strict_types=1);

namespace App\AlbumFilters;

final readonly class AlbumFilterBag
{
    private const array MAPPING = [
        'cs' => 'catch_states',
        'f' => 'families',
        'fc' => 'category_forms',
        'fr' => 'regional_forms',
        'fs' => 'special_forms',
        'fv' => 'variant_forms',
        'at' => 'any_types',
        't1' => 'primary_types',
        't2' => 'secondary_types',
        'ogb' => 'original_game_bundles',
        'gba' => 'game_bundle_availabilities',
        'gbsa' => 'game_bundle_shiny_availabilities',
        'ca' => 'collection_availabilities',
    ];

    /**
     * @param array<string, string>   $stringFilters
     * @param array<string, string[]> $multipleFilters
     */
    public function __construct(
        public array $stringFilters = [],
        public array $multipleFilters = [],
    ) {}

    /**
     * @return array<string, string|string[]>
     */
    public function toRouteParams(): array
    {
        return array_merge($this->stringFilters, $this->multipleFilters);
    }

    /**
     * @return array<string, string[]>
     */
    public function toApiParams(): array
    {
        $result = [];

        foreach ($this->stringFilters as $key => $value) {
            $result[self::MAPPING[$key]] = [$value];
        }

        foreach ($this->multipleFilters as $key => $values) {
            $result[self::MAPPING[$key]] = $values;
        }

        return $result;
    }
}
