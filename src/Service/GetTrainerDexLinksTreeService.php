<?php

declare(strict_types=1);

namespace App\Service;

use App\DTO\TrainerDexLinkEdge;
use App\DTO\TrainerDexLinksTree;
use App\ResponseObject\Album\DexListItem;
use App\ResponseObject\Album\TrainerDexLink;
use App\Service\Back\GetTrainerDexListService;
use App\Service\Back\TrainerDexLinkService;
use Symfony\Contracts\HttpClient\Exception\HttpExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

/**
 * Aggregates the trainer dex links across every one of the trainer's dexes into a single,
 * deduplicated set of edges, since the back-end only exposes links one dex at a time
 * (`GET /album_link/{dexSlug}`).
 */
class GetTrainerDexLinksTreeService
{
    public function __construct(
        private readonly GetTrainerDexListService $getTrainerDexListService,
        private readonly TrainerDexLinkService $trainerDexLinkService,
    ) {}

    public function getTree(): TrainerDexLinksTree
    {
        $dexList = $this->getTrainerDexListService->get();

        /** @var array<string, DexListItem> $dexBySlug */
        $dexBySlug = [];
        foreach ($dexList as $item) {
            $dexBySlug[$item->getSettings()->getSlug()] = $item;
        }

        /** @var array<string, TrainerDexLinkEdge> $edgesByPairKey */
        $edgesByPairKey = [];

        foreach ($dexList as $item) {
            $sourceSlug = $item->getSettings()->getSlug();

            foreach ($this->listLinksFor($sourceSlug) as $link) {
                $edge = $this->resolveEdge($sourceSlug, $link, $dexBySlug);

                if (null === $edge) {
                    continue;
                }

                $pairKey = $this->pairKey($edge->getFrom()->getSettings()->getSlug(), $edge->getTo()->getSettings()->getSlug());

                // A pair already known to be bidirectional stays bidirectional, whichever
                // side of the pair it gets reported from next.
                if (isset($edgesByPairKey[$pairKey]) && 'both' === $edgesByPairKey[$pairKey]->getMode()) {
                    continue;
                }

                $edgesByPairKey[$pairKey] = $edge;
            }
        }

        return new TrainerDexLinksTree(array_values($edgesByPairKey));
    }

    /**
     * @return TrainerDexLink[]
     */
    private function listLinksFor(string $dexSlug): array
    {
        try {
            return $this->trainerDexLinkService->list($dexSlug);
        } catch (HttpExceptionInterface|TransportExceptionInterface) {
            return [];
        }
    }

    /**
     * @param array<string, DexListItem> $dexBySlug
     */
    private function resolveEdge(
        string $sourceSlug,
        TrainerDexLink $link,
        array $dexBySlug,
    ): ?TrainerDexLinkEdge {
        $targetSlug = $link->getTargetDexSlug();

        [$fromSlug, $toSlug, $mode] = match ($link->getDirection()) {
            'from' => [$targetSlug, $sourceSlug, 'to'],
            'both' => [$sourceSlug, $targetSlug, 'both'],
            default => [$sourceSlug, $targetSlug, 'to'],
        };

        $from = $dexBySlug[$fromSlug] ?? null;
        $toDex = $dexBySlug[$toSlug] ?? null;

        if (null === $from || null === $toDex) {
            return null;
        }

        return new TrainerDexLinkEdge($link->getId(), $mode, $from, $toDex);
    }

    private function pairKey(string $slugA, string $slugB): string
    {
        return $slugA < $slugB ? "{$slugA}|{$slugB}" : "{$slugB}|{$slugA}";
    }
}
