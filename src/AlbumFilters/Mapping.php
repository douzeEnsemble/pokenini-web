<?php

declare(strict_types=1);

namespace App\AlbumFilters;

class Mapping
{
    private const array FILTERS = [
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
     * @param string[]|string[][] $filters
     *
     * @return string[]|string[][]
     */
    public static function get(array $filters): array
    {
        $mappedFilters = [];

        foreach ($filters as $filterName => $value) {
            $newKey = self::FILTERS[$filterName];
            $mappedFilters[$newKey] = is_array($value) ? $value : [$value];
        }

        return $mappedFilters;
    }
}
