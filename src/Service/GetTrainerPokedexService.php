<?php

declare(strict_types=1);

namespace App\Service;

use App\ResponseObject\Album\Album;
use App\Service\Back\GetPokedexService;
use Symfony\Contracts\HttpClient\Exception\HttpExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class GetTrainerPokedexService
{
    public function __construct(
        private readonly GetPokedexService $getPokedexService,
        private readonly LocaleService $localeService,
    ) {}

    /**
     * @param string[]|string[][] $filters
     */
    public function getPokedexData(string $dexSlug, array $filters, ?string $trainerId = null): ?Album
    {
        $locale = $this->localeService->current();

        try {
            if (null !== $trainerId && !empty($trainerId)) {
                return $this->getPokedexService->getWithTrainerId($trainerId, $dexSlug, $filters, $locale);
            }

            return $this->getPokedexService->get($dexSlug, $filters, $locale);
        } catch (HttpExceptionInterface|TransportExceptionInterface $e) {
            return null;
        }
    }
}
