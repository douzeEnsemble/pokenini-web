<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\DTO\HubElection;
use App\ResponseObject\Album\DexFlags;
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
final class HubHighlightsServiceElectionTest extends TestCase
{
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

    private static function flags(): DexFlags
    {
        return new DexFlags(
            isShiny: false,
            isPrivate: false,
            isOnHome: false,
            isDisplayForm: false,
            isReleased: true,
            isPremium: false,
            isCustom: false,
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
