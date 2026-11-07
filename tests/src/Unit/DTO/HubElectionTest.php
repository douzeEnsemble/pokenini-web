<?php

declare(strict_types=1);

namespace App\Tests\Unit\DTO;

use App\DTO\HubElection;
use App\ResponseObject\Album\DexFlags;
use App\ResponseObject\Election\ElectionDexListItem;
use App\ResponseObject\Election\ElectionReport;
use App\ResponseObject\Election\ElectionReportCompletion;
use App\ResponseObject\Election\ElectionReportMetrics;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(HubElection::class)]
final class HubElectionTest extends TestCase
{
    #[Test]
    public function notStartedWithoutReport(): void
    {
        $dex = self::dex(null);
        $election = new HubElection($dex);

        $this->assertSame($dex, $election->dex);
        $this->assertSame(HubElection::NOT_STARTED, $election->state);
        $this->assertFalse($election->isInProgress());
    }

    #[Test]
    public function notStartedWithoutRound(): void
    {
        $election = new HubElection(self::dex(self::report(roundCount: 0, atMaxCount: 0, underMaxCount: 10)));

        $this->assertSame(HubElection::NOT_STARTED, $election->state);
        $this->assertFalse($election->isInProgress());
    }

    #[Test]
    public function inProgress(): void
    {
        $election = new HubElection(self::dex(self::report(roundCount: 4, atMaxCount: 2, underMaxCount: 8)));

        $this->assertSame(HubElection::IN_PROGRESS, $election->state);
        $this->assertTrue($election->isInProgress());
    }

    #[Test]
    public function inProgressWhenNotEveryPokemonIsAtMax(): void
    {
        // Nothing under the maximum, but not every Pokémon reached it yet
        $election = new HubElection(self::dex(self::report(roundCount: 4, atMaxCount: 9, underMaxCount: 0)));

        $this->assertSame(HubElection::IN_PROGRESS, $election->state);
    }

    #[Test]
    public function inProgressWhenSomePokemonAreUnderMax(): void
    {
        $election = new HubElection(self::dex(self::report(roundCount: 4, atMaxCount: 10, underMaxCount: 1)));

        $this->assertSame(HubElection::IN_PROGRESS, $election->state);
    }

    #[Test]
    public function finished(): void
    {
        $election = new HubElection(self::dex(self::report(roundCount: 12, atMaxCount: 10, underMaxCount: 0)));

        $this->assertSame(HubElection::FINISHED, $election->state);
        $this->assertFalse($election->isInProgress());
    }

    private static function report(int $roundCount, int $atMaxCount, int $underMaxCount): ElectionReport
    {
        return new ElectionReport(new ElectionReportMetrics(
            completion: new ElectionReportCompletion(atMaxCount: $atMaxCount, underMaxCount: $underMaxCount),
            dexTotalCount: 10,
            roundCount: $roundCount,
            totalRoundCount: 12,
        ));
    }

    private static function dex(?ElectionReport $report): ElectionDexListItem
    {
        return new ElectionDexListItem(
            slug: 'home',
            originalSlug: 'home',
            name: 'Home',
            frenchName: 'Home',
            flags: new DexFlags(
                isShiny: false,
                isPrivate: false,
                isOnHome: false,
                isDisplayForm: false,
                isReleased: true,
                isPremium: false,
                isCustom: false,
            ),
            displayTemplate: 'box',
            description: null,
            frenchDescription: null,
            dexTotalCount: 10,
            report: $report,
        );
    }
}
