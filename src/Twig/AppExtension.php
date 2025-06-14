<?php

declare(strict_types=1);

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class AppExtension extends AbstractExtension
{
    #[\Override]
    public function getFilters(): array
    {
        return [
            new TwigFilter('ksort', [$this, 'ksort']),
            new TwigFilter('sha1', [$this, 'sha1']),
        ];
    }

    #[\Override]
    public function getFunctions(): array
    {
        return [
            new TwigFunction('version', [$this, 'getVersion']),
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

    public function sha1(string $value): string
    {
        return sha1($value);
    }

    public function getVersion(string $filename = 'version'): string
    {
        $version = file_get_contents(__DIR__.'/../../resources/metadata/'.$filename);

        return false === $version ? '0.0.toto' : $version;
    }
}
