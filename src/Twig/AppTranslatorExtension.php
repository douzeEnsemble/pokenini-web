<?php

declare(strict_types=1);

namespace App\Twig;

use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class AppTranslatorExtension extends AbstractExtension
{
    public function __construct(private readonly TranslatorInterface $translator) {}

    #[\Override]
    public function getFilters(): array
    {
        return [
            new TwigFilter('almost_exactly', [$this, 'almostExactly']),
        ];
    }

    public function almostExactly(float $value): string
    {
        $floor = floor($value);
        $ceil = ceil($value);

        $average = ($floor + $ceil) / 2;

        if ($value == $floor) {
            return $this->translator->trans('number.exactly', ['number' => $floor]);
        }
        if ($value < $floor + 0.25) {
            return $this->translator->trans('number.almost', ['number' => $floor]);
        }
        if ($value > $ceil - 0.25) {
            return $this->translator->trans('number.almost', ['number' => $ceil]);
        }
        if ($average > $value) {
            return $this->translator->trans('number.approximately', ['number' => $floor]);
        }
        if ($average < $value) {
            return $this->translator->trans('number.approximately', ['number' => $ceil]);
        }

        return $this->translator->trans('number.between', ['low' => $floor, 'high' => $ceil]);
    }
}
