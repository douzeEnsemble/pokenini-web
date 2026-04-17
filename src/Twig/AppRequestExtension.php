<?php

declare(strict_types=1);

namespace App\Twig;

use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Attribute\AsTwigFunction;

class AppRequestExtension
{
    public function __construct(private readonly RequestStack $requestStack) {}

    /**
     * @return array<int, string>
     */
    #[AsTwigFunction('getArrayFromRequest')]
    public function getArrayFromRequest(string $name): array
    {
        $values = $this->requestStack->getCurrentRequest()?->query->all();

        if (!isset($values[$name])) {
            return [];
        }

        /**
         * @var array<int, string>
         */
        return $values[$name];
    }
}
