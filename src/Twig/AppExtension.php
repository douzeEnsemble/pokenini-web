<?php

declare(strict_types=1);

namespace App\Twig;

use App\Service\AppVersionService;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

final class AppExtension extends AbstractExtension
{
    public function __construct(private readonly AppVersionService $versionService) {}

    #[\Override]
    public function getFilters(): array
    {
        return [
            new TwigFilter('ksort', [$this, 'ksort']),
            new TwigFilter('sha1', [$this, 'sha1']),
            new TwigFilter('htmlNl2br', [$this, 'htmlNl2br'], ['is_safe' => ['html']]),
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

    public function htmlNl2br(string $value): string
    {
        return nl2br($value, false);
    }

    public function getVersion(string $filename = 'version'): string
    {
        return $this->versionService->getVersion($filename);
    }
}
