<?php

declare(strict_types=1);

namespace App\DTO;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class DexFiltersRequest
{
    public static function dexFiltersFromRequest(Request $request): DexFilters
    {
        $resolver = new OptionsResolver();

        $resolver->setDefaults([
            'p' => '',
            'h' => '',
            'r' => '',
            's' => '',
            'm' => '',
        ]);

        $options = $resolver->resolve($request->query->all());

        return DexFilters::createFromArray([
            'privacy' => $options['p'],
            'homepaged' => $options['h'],
            'released' => $options['r'],
            'shiny' => $options['s'],
            'premium' => $options['m'],
        ]);
    }
}
