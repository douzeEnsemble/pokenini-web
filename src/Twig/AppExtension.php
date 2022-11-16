<?php

declare(strict_types=1);

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class AppExtension extends AbstractExtension
{
    public const BANNER_DIR = '/img/dex/banner/';

    public function __construct(private readonly string $projectDir)
    {
    }

    /**
     * @return TwigFunction[]
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('banner_exists', [$this, 'isBannerFileExists']),
            new TwigFunction('dex_banner', [$this, 'getDexBanner']),
        ];
    }

    public function isBannerFileExists(string $bannerName): bool
    {
        return file_exists("{$this->projectDir}/public/" . self::BANNER_DIR . "/$bannerName.png");
    }

    public function getDexBanner(string $dexSlug): string
    {
        $dexBannerPath = self::BANNER_DIR . $dexSlug . '.png';
        $defaultBannerPath = self::BANNER_DIR . 'default.png';

        return $this->isBannerFileExists($dexSlug) ? $dexBannerPath : $defaultBannerPath;
    }
}
