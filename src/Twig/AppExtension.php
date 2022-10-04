<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class AppExtension extends AbstractExtension
{
    public function __construct(private readonly string $projectDir)
    {
    }

    /**
     * @return TwigFunction[]
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('banner_exists', [$this, 'bannerFileExists']),
        ];
    }

    public function bannerFileExists(string $bannerName): bool
    {
        return file_exists("{$this->projectDir}/public/img/dex/banner/$bannerName.png");
    }
}
