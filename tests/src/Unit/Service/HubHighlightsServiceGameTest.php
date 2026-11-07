<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\DTO\HubGame;
use App\ResponseObject\ColorGame\ColorGame;
use App\ResponseObject\ColorGame\ColorGameDexSummary;
use App\Service\Back\ColorGameService;
use App\Service\Back\GetAlbumDexListService;
use App\Service\Back\GetElectionDexListService;
use App\Service\Back\ShinyGameService;
use App\Service\Back\WhichShinyGameService;
use App\Service\HubHighlightsService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(HubHighlightsService::class)]
final class HubHighlightsServiceGameTest extends TestCase
{
    #[Test]
    public function games(): void
    {
        $colorGame = new ColorGame(
            id: 'color',
            dex: new ColorGameDexSummary(slug: 'home', name: 'Home', frenchName: 'Home'),
            isShiny: false,
            status: 'in_progress',
            roundCount: 5,
            answeredCount: 3,
            correctCount: 2,
            startedAt: '2026-11-01T10:00:00+00:00',
            lastPlayedAt: null,
            endedAt: null,
        );

        $colorGameService = $this->createMock(ColorGameService::class);
        $colorGameService->expects($this->once())->method('getGames')->willReturn([$colorGame]);
        $shinyGameService = $this->createMock(ShinyGameService::class);
        $shinyGameService->expects($this->once())->method('getGames')->willReturn([]);
        $whichShinyGameService = $this->createMock(WhichShinyGameService::class);
        $whichShinyGameService->expects($this->once())->method('getGames')->willReturn([]);

        $games = $this->service(
            colorGameService: $colorGameService,
            shinyGameService: $shinyGameService,
            whichShinyGameService: $whichShinyGameService,
        )->getGames();

        $this->assertSame(
            [HubGame::COLOR, HubGame::SHINY, HubGame::WHICH_SHINY],
            array_map(static fn (HubGame $game): string => $game->type, $games),
        );
        $this->assertSame($colorGame, $games[0]->game);
        $this->assertNull($games[1]->game);
        $this->assertNull($games[2]->game);
    }

    private function service(
        ?GetAlbumDexListService $albumService = null,
        ?GetElectionDexListService $electionService = null,
        ?ColorGameService $colorGameService = null,
        ?ShinyGameService $shinyGameService = null,
        ?WhichShinyGameService $whichShinyGameService = null,
    ): HubHighlightsService {
        return new HubHighlightsService(
            $albumService ?? $this->createStub(GetAlbumDexListService::class),
            $electionService ?? $this->createStub(GetElectionDexListService::class),
            $colorGameService ?? $this->createStub(ColorGameService::class),
            $shinyGameService ?? $this->createStub(ShinyGameService::class),
            $whichShinyGameService ?? $this->createStub(WhichShinyGameService::class),
        );
    }
}
