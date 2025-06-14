<?php

declare(strict_types=1);

namespace App\AlbumFilters;

use Symfony\Component\HttpFoundation\Request;

class FromRequest
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

    /**
     * @return string[]|string[][]
     */
    public static function get(Request $request): array
    {
        $filters = [];

        foreach (self::STRING_FILTERS as $filterName) {
            if ($request->query->has($filterName)) {
                $filters[$filterName] = $request->query->getString($filterName);
            }
        }

        foreach (self::MULTIPLE_FILTERS as $filterName) {
            if ($request->query->has($filterName)) {
                /** @var null|string[] $values */
                $values = $request->query->all()[$filterName];
                $values ??= [];
                $filters[$filterName] = array_filter($values);
            }
        }

        return $filters;
    }
}
