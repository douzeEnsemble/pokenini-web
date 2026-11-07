<?php

declare(strict_types=1);

namespace App\Service;

use App\DTO\HubElection;
use App\DTO\HubGame;
use App\ResponseObject\Album\DexListItem;
use App\Service\Back\ColorGameService;
use App\Service\Back\GetAlbumDexListService;
use App\Service\Back\GetElectionDexListService;
use App\Service\Back\ShinyGameService;
use App\Service\Back\WhichShinyGameService;

/**
 * What the hub puts forward for the logged trainer: albums they chose, elections and games picked for them.
 *
 * Every list comes from the back, which keeps it in cache.
 */
class HubHighlightsService
{
    public const int MAX_ALBUMS = 3;
    public const int MAX_ELECTIONS = 3;

    public function __construct(
        private readonly GetAlbumDexListService $albumDexListService,
        private readonly GetElectionDexListService $electionDexListService,
        private readonly ColorGameService $colorGameService,
        private readonly ShinyGameService $shinyGameService,
        private readonly WhichShinyGameService $whichShinyGameService,
    ) {}

    /**
     * The albums the trainer put on the hub.
     *
     * @return DexListItem[]
     */
    public function getAlbums(): array
    {
        $albums = array_filter(
            $this->albumDexListService->get(),
            static fn (DexListItem $dex): bool => $dex->getFlags()->isOnHub(),
        );

        return \array_slice(array_values($albums), 0, self::MAX_ALBUMS);
    }

    /**
     * The elections started and not finished first, then the ones not started, then the finished ones.
     *
     * @return HubElection[]
     */
    public function getElections(): array
    {
        $groups = [HubElection::IN_PROGRESS => [], HubElection::NOT_STARTED => [], HubElection::FINISHED => []];

        foreach ($this->electionDexListService->get() as $dex) {
            $election = new HubElection($dex);
            $groups[$election->state][] = $election;
        }

        return \array_slice(array_merge(...array_values($groups)), 0, self::MAX_ELECTIONS);
    }

    /**
     * Each game with the trainer's game to resume or to play again.
     *
     * @return HubGame[]
     */
    public function getGames(): array
    {
        return [
            HubGame::fromGames(HubGame::COLOR, $this->colorGameService->getGames()),
            HubGame::fromGames(HubGame::SHINY, $this->shinyGameService->getGames()),
            HubGame::fromGames(HubGame::WHICH_SHINY, $this->whichShinyGameService->getGames()),
        ];
    }
}
