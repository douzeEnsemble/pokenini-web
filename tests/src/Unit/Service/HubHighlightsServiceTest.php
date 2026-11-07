<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\DTO\HubElection;
use App\DTO\HubGame;
use App\ResponseObject\Album\DexFlags;
use App\ResponseObject\Album\DexListItem;
use App\ResponseObject\Album\DexListItemRef;
use App\ResponseObject\Album\DexListItemSettings;
use App\ResponseObject\ColorGame\ColorGame;
use App\ResponseObject\ColorGame\ColorGameDexSummary;
use App\ResponseObject\Election\ElectionDexListItem;
use App\ResponseObject\Election\ElectionReport;
use App\ResponseObject\Election\ElectionReportCompletion;
use App\ResponseObject\Election\ElectionReportMetrics;
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
final class HubHighlightsServiceTest extends TestCase
{
    #[Test]
    public function albums(): void
    {
        $albumService = $this->createMock(GetAlbumDexListService::class);
        $albumService->expects($this->once())
            ->method('get')
            ->with(null)
            ->willReturn([
                self::album('a', false),
                self::album('b', true),
                self::album('c', true),
                self::album('d', false),
                self::album('e', true),
                self::album('f', true),
            ])
        ;

        $albums = $this->service(albumService: $albumService)->getAlbums();

        $this->assertSame(
            ['b', 'c', 'e'],
            array_map(static fn (DexListItem $item): string => $item->getDex()->getSlug(), $albums),
        );
        $this->assertSame([0, 1, 2], array_keys($albums));
    }

    #[Test]
    public function noAlbum(): void
    {
        $albumService = $this->createStub(GetAlbumDexListService::class);
        $albumService->method('get')->willReturn([self::album('a', false)]);

        $this->assertSame([], $this->service(albumService: $albumService)->getAlbums());
    }

    #[Test]
    public function elections(): void
    {
        $electionService = $this->createMock(GetElectionDexListService::class);
        $electionService->expects($this->once())
            ->method('get')
            ->willReturn([
                self::election('finished', 12, 0),
                self::election('not-started', 0, 10),
                self::election('in-progress-1', 4, 6),
                self::election('in-progress-2', 2, 8),
            ])
        ;

        $this->assertSame(
            ['in-progress-1', 'in-progress-2', 'not-started'],
            self::electionSlugs($this->service(electionService: $electionService)->getElections()),
        );
    }

    #[Test]
    public function electionsFillWithFinishedOnes(): void
    {
        $electionService = $this->createStub(GetElectionDexListService::class);
        $electionService->method('get')->willReturn([
            self::election('finished-1', 12, 0),
            self::election('in-progress', 4, 6),
            self::election('finished-2', 12, 0),
        ]);

        $this->assertSame(
            ['in-progress', 'finished-1', 'finished-2'],
            self::electionSlugs($this->service(electionService: $electionService)->getElections()),
        );
    }

    #[Test]
    public function noElection(): void
    {
        $electionService = $this->createStub(GetElectionDexListService::class);
        $electionService->method('get')->willReturn([]);

        $this->assertSame([], $this->service(electionService: $electionService)->getElections());
    }

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

    /**
     * @param HubElection[] $elections
     *
     * @return string[]
     */
    private static function electionSlugs(array $elections): array
    {
        return array_map(static fn (HubElection $election): string => $election->dex->getSlug(), $elections);
    }

    private static function flags(bool $isOnHub = false): DexFlags
    {
        return new DexFlags(
            isShiny: false,
            isPrivate: false,
            isOnHome: false,
            isDisplayForm: false,
            isReleased: true,
            isPremium: false,
            isCustom: false,
            isOnHub: $isOnHub,
        );
    }

    private static function album(string $slug, bool $isOnHub): DexListItem
    {
        return new DexListItem(
            dex: new DexListItemRef(slug: $slug),
            settings: new DexListItemSettings(
                name: $slug,
                frenchName: $slug,
                slug: $slug,
                displayTemplate: 'box',
            ),
            flags: self::flags($isOnHub),
        );
    }

    private static function election(string $slug, int $roundCount, int $underMaxCount): ElectionDexListItem
    {
        return new ElectionDexListItem(
            slug: $slug,
            originalSlug: $slug,
            name: $slug,
            frenchName: $slug,
            flags: self::flags(),
            displayTemplate: 'box',
            description: null,
            frenchDescription: null,
            dexTotalCount: 10,
            report: new ElectionReport(new ElectionReportMetrics(
                completion: new ElectionReportCompletion(atMaxCount: 10 - $underMaxCount, underMaxCount: $underMaxCount),
                dexTotalCount: 10,
                roundCount: $roundCount,
                totalRoundCount: 12,
            )),
        );
    }
}
