<?php

declare(strict_types=1);

namespace App\DTO;

use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class DexFilters
{
    /**
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    private function __construct(
        public DexFilterValue $privacy,
        public DexFilterValue $homepaged,
        public DexFilterValue $released,
        public DexFilterValue $shiny,
        public DexFilterValue $premium,
    ) {}

    /**
     * @param string[] $data
     */
    public static function createFromArray(array $data): self
    {
        $resolver = new OptionsResolver();

        $defaultsValues = [
            'privacy' => '',
            'homepaged' => '',
            'released' => '',
            'shiny' => '',
            'premium' => '',
        ];

        $resolver->setDefaults($defaultsValues);

        foreach (array_keys($defaultsValues) as $key) {
            $resolver->setNormalizer(
                $key,
                function (Options $options, string $data): DexFilterValue {
                    return self::normalizer($data);
                }
            );
        }

        $options = $resolver->resolve($data);

        return new self(
            $options['privacy'],
            $options['homepaged'],
            $options['released'],
            $options['shiny'],
            $options['premium'],
        );
    }

    public static function normalizer(string $data): DexFilterValue
    {
        $newData = ('' == $data) ? null : (bool) $data;

        return new DexFilterValue($newData);
    }
}
