<?php

declare(strict_types=1);

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class AppExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('ksort', [$this, 'ksort']),
        ];
    }

    /**
     * @param mixed[] $array
     *
     * @return mixed[]
     */
    public function ksort(array $array, int $flags = SORT_REGULAR): array
    {
        ksort($array, $flags);

        return $array;
    }
}
