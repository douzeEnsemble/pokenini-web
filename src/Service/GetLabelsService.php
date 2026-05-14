<?php

declare(strict_types=1);

namespace App\Service;

use App\ResponseObject\Label\CatchState;
use App\ResponseObject\Label\Labels;
use App\Service\Back\GetLabelsService as BackGetLabelsService;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

class GetLabelsService
{
    public function __construct(
        private readonly BackGetLabelsService $getService,
        #[Autowire(service: 'cache.labels')]
        private readonly TagAwareCacheInterface $labelsCache,
    ) {}

    /**
     * @return array<int, CatchState>
     */
    public function getCatchStates(): array
    {
        return $this->getLabels()->getCatchStates();
    }

    public function getAllLabels(): Labels
    {
        return $this->getLabels();
    }

    private function getLabels(): Labels
    {
        return $this->labelsCache->get('labels', function (ItemInterface $item): Labels {
            $item->tag(['labels']);

            return $this->getService->get();
        });
    }
}
