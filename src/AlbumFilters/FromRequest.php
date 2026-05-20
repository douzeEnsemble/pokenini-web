<?php

declare(strict_types=1);

namespace App\AlbumFilters;

use Symfony\Component\HttpFoundation\Request;

final class FromRequest
{
    private const array STRING_FILTERS = [
        'cs',
        'f',
    ];

    private const array MULTIPLE_FILTERS = [
        'fc',
        'fr',
        'fs',
        'fv',
        'at',
        't1',
        't2',
        'ogb',
        'gba',
        'gbsa',
        'ca',
    ];

    public static function get(Request $request): AlbumFilterBag
    {
        $stringFilters = [];
        $multipleFilters = [];

        foreach (self::STRING_FILTERS as $filterName) {
            if ($request->query->has($filterName)) {
                $stringFilters[$filterName] = $request->query->getString($filterName);
            }
        }

        foreach (self::MULTIPLE_FILTERS as $filterName) {
            if ($request->query->has($filterName)) {
                /** @var null|string[] $values */
                $values = $request->query->all()[$filterName];
                $values ??= [];
                $multipleFilters[$filterName] = array_filter($values);
            }
        }

        return new AlbumFilterBag(
            stringFilters: $stringFilters,
            multipleFilters: $multipleFilters,
        );
    }
}
