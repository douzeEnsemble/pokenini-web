<?php

declare(strict_types=1);

namespace App\Service;

use App\ResponseObject\Common\NamesMap;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Single entry point for everything language related in the views.
 *
 * Names are handled as maps indexed by locale (["en" => "...", "fr" => "..."]),
 * so adding a language does not change any template.
 */
class LocaleService
{
    /**
     * @param list<string> $locales enabled locales, in display order
     */
    public function __construct(
        private readonly RequestStack $requestStack,
        #[Autowire(param: 'app.locales')]
        private readonly array $locales,
        #[Autowire(param: 'app.fallback_locale')]
        private readonly string $fallbackLocale,
    ) {}

    /**
     * The locale of the current request, or the fallback one if it is not an enabled locale.
     */
    public function current(): string
    {
        $locale = $this->requestStack->getCurrentRequest()?->getLocale();

        if (null !== $locale && \in_array($locale, $this->locales, true)) {
            return $locale;
        }

        return $this->fallbackLocale;
    }

    /**
     * @return list<string> the enabled locales, in display order
     */
    public function locales(): array
    {
        return $this->locales;
    }

    /**
     * The name to display in the current locale, falling back to the fallback locale.
     *
     * @param null|array<string, null|string> $names
     */
    public function localize(?array $names): string
    {
        $name = $names[$this->current()] ?? null;

        if (null !== $name && '' !== $name) {
            return $name;
        }

        $fallbackName = $names[$this->fallbackLocale] ?? null;

        if (null !== $fallbackName && '' !== $fallbackName) {
            return $fallbackName;
        }

        return $names[NamesMap::RESOLVED] ?? '';
    }

    /**
     * The list to display in the current locale, falling back to the fallback locale.
     *
     * @param array<string, list<string>|string[]> $lists
     *
     * @return string[]
     */
    public function localizeList(array $lists): array
    {
        $list = $lists[$this->current()] ?? [];

        if ([] !== $list) {
            return $list;
        }

        $fallbackList = $lists[$this->fallbackLocale] ?? [];

        if ([] !== $fallbackList) {
            return $fallbackList;
        }

        return $lists[NamesMap::RESOLVED] ?? [];
    }

    /**
     * The non empty names, the current locale first, then the enabled locales in their configured order.
     *
     * @param null|array<string, null|string> $names
     *
     * @return array<string, string>
     */
    public function ordered(?array $names): array
    {
        $ordered = [];
        // The current locale is listed twice, but only its first position counts
        foreach ([$this->current(), ...$this->locales] as $locale) {
            $name = $names[$locale] ?? null;
            if (null !== $name && '' !== $name) {
                $ordered[$locale] = $name;
            }
        }

        return $ordered;
    }
}
