<?php

declare(strict_types=1);

namespace App\Twig;

use App\Service\LocaleService;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

/**
 * Choose, among a map of names indexed by locale, the one(s) to display.
 */
final class LocaleExtension extends AbstractExtension
{
    public function __construct(private readonly LocaleService $localeService) {}

    #[\Override]
    public function getFunctions(): array
    {
        return [
            new TwigFunction('enabled_locales', [$this->localeService, 'locales']),
        ];
    }

    #[\Override]
    public function getFilters(): array
    {
        return [
            new TwigFilter('localized', [$this->localeService, 'localize']),
            new TwigFilter('names_by_locale', [$this->localeService, 'ordered']),
            new TwigFilter('localized_list', [$this->localeService, 'localizeList']),
        ];
    }
}
