<?php

declare(strict_types=1);

namespace App\Service;

use App\ResponseObject\Common\PokemonCreditRow;
use App\Service\Back\GetCreditsService as BackGetCreditsService;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

class GetCreditsService
{
    public function __construct(
        private readonly BackGetCreditsService $getService,
        #[Autowire(service: 'cache.labels')]
        private readonly TagAwareCacheInterface $creditsCache,
    ) {}

    /**
     * @return PokemonCreditRow[]
     */
    public function get(): array
    {
        return $this->creditsCache->get('credits_v3', function (ItemInterface $item): array {
            $item->tag(['credits_v3']);

            return $this->getService->get();
        });
    }
}
