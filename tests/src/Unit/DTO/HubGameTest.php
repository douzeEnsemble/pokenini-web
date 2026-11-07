<?php

declare(strict_types=1);

namespace App\Tests\Unit\DTO;

use App\DTO\HubGame;
use App\ResponseObject\ColorGame\ColorGame;
use App\ResponseObject\ColorGame\ColorGameDexSummary;
use App\ResponseObject\ShinyGame\ShinyGame;
use App\ResponseObject\ShinyGame\ShinyGameDexSummary;
use App\ResponseObject\WhichShinyGame\WhichShinyGame;
use App\ResponseObject\WhichShinyGame\WhichShinyGameDexSummary;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(HubGame::class)]
final class HubGameTest extends TestCase
{
    #[Test]
    public function noGame(): void
    {
        $hubGame = HubGame::fromGames(HubGame::COLOR, []);

        $this->assertSame(HubGame::COLOR, $hubGame->type);
        $this->assertNull($hubGame->game);
        $this->assertFalse($hubGame->isInProgress());
    }

    #[Test]
    public function lastInProgressGameFirst(): void
    {
        $finishedLater = self::makeColorGame('finished-later', 'finished', '2026-11-07T10:00:00+00:00', '2026-11-07T12:00:00+00:00');
        $inProgressOld = self::makeColorGame('in-progress-old', 'in_progress', '2026-11-01T10:00:00+00:00', '2026-11-01T11:00:00+00:00');
        $inProgressNew = self::makeColorGame('in-progress-new', 'in_progress', '2026-11-02T10:00:00+00:00', '2026-11-03T11:00:00+00:00');
        $inProgressMiddle = self::makeColorGame('in-progress-middle', 'in_progress', '2026-11-02T09:00:00+00:00', '2026-11-02T11:00:00+00:00');

        $hubGame = HubGame::fromGames(HubGame::COLOR, [$finishedLater, $inProgressOld, $inProgressNew, $inProgressMiddle]);

        $this->assertSame($inProgressNew, $hubGame->game);
        $this->assertTrue($hubGame->isInProgress());
    }

    #[Test]
    public function lastGameWhenNoneInProgress(): void
    {
        $old = self::makeColorGame('old', 'finished', '2026-11-01T10:00:00+00:00', '2026-11-01T11:00:00+00:00');
        $new = self::makeColorGame('new', 'abandoned', '2026-11-03T10:00:00+00:00', '2026-11-03T11:00:00+00:00');
        $middle = self::makeColorGame('middle', 'finished', '2026-11-02T10:00:00+00:00', '2026-11-02T11:00:00+00:00');

        $hubGame = HubGame::fromGames(HubGame::COLOR, [$old, $new, $middle]);

        $this->assertSame($new, $hubGame->game);
        $this->assertFalse($hubGame->isInProgress());
    }

    #[Test]
    public function startedAtWhenNeverPlayed(): void
    {
        $playedBefore = self::makeColorGame('played-before', 'finished', '2026-11-01T10:00:00+00:00', '2026-11-02T11:00:00+00:00');
        $startedAfter = self::makeColorGame('started-after', 'finished', '2026-11-03T10:00:00+00:00', null);

        $hubGame = HubGame::fromGames(HubGame::COLOR, [$playedBefore, $startedAfter]);

        $this->assertSame($startedAfter, $hubGame->game);
    }

    #[Test]
    public function equalDatesKeepTheFirstGame(): void
    {
        $first = self::makeColorGame('first', 'finished', '2026-11-01T10:00:00+00:00', '2026-11-01T11:00:00+00:00');
        $second = self::makeColorGame('second', 'finished', '2026-11-01T10:00:00+00:00', '2026-11-01T11:00:00+00:00');

        $this->assertSame($first, HubGame::fromGames(HubGame::COLOR, [$first, $second])->game);
    }

    #[Test]
    public function datesAreComparedAsDates(): void
    {
        // As strings, "2026-11-01T12:00:00+02:00" is after "2026-11-01T11:00:00+00:00", but it is 10:00 UTC
        $earlier = self::makeColorGame('earlier', 'finished', '2026-11-01T09:00:00+00:00', '2026-11-01T12:00:00+02:00');
        $later = self::makeColorGame('later', 'finished', '2026-11-01T09:00:00+00:00', '2026-11-01T11:00:00+00:00');

        $this->assertSame($later, HubGame::fromGames(HubGame::COLOR, [$earlier, $later])->game);
    }

    #[Test]
    public function colorGameRoutes(): void
    {
        $hubGame = new HubGame(HubGame::COLOR, null);

        $this->assertSame('color_game.title', $hubGame->getTitle());
        $this->assertSame('img/game/color.svg', $hubGame->getIcon());
        $this->assertSame('color_game', $hubGame->getCsrfTokenId());
        $this->assertSame('app_colorgame_dex', $hubGame->getDexRoute());
        $this->assertSame('app_colorgame_play', $hubGame->getPlayRoute());
        $this->assertSame('app_colorgame_create', $hubGame->getCreateRoute());
    }

    #[Test]
    public function shinyGameRoutes(): void
    {
        $game = new ShinyGame(
            id: 'shiny',
            dex: new ShinyGameDexSummary(slug: 'home', name: 'Home', frenchName: 'Home'),
            status: 'in_progress',
            roundCount: 10,
            answeredCount: 3,
            correctCount: 2,
            startedAt: '2026-11-01T10:00:00+00:00',
            lastPlayedAt: null,
            endedAt: null,
        );

        $hubGame = HubGame::fromGames(HubGame::SHINY, [$game]);

        $this->assertSame($game, $hubGame->game);
        $this->assertTrue($hubGame->isInProgress());
        $this->assertSame('shiny_game.title', $hubGame->getTitle());
        $this->assertSame('img/game/shiny.svg', $hubGame->getIcon());
        $this->assertSame('shiny_game', $hubGame->getCsrfTokenId());
        $this->assertSame('app_shinygame_dex', $hubGame->getDexRoute());
        $this->assertSame('app_shinygame_play', $hubGame->getPlayRoute());
        $this->assertSame('app_shinygame_create', $hubGame->getCreateRoute());
    }

    #[Test]
    public function whichShinyGameRoutes(): void
    {
        $game = new WhichShinyGame(
            id: 'which-shiny',
            dex: new WhichShinyGameDexSummary(slug: 'home', name: 'Home', frenchName: 'Home'),
            status: 'finished',
            roundCount: 10,
            answeredCount: 10,
            correctCount: 8,
            startedAt: '2026-11-01T10:00:00+00:00',
            lastPlayedAt: '2026-11-01T10:30:00+00:00',
            endedAt: '2026-11-01T10:30:00+00:00',
        );

        $hubGame = HubGame::fromGames(HubGame::WHICH_SHINY, [$game]);

        $this->assertSame($game, $hubGame->game);
        $this->assertFalse($hubGame->isInProgress());
        $this->assertSame('which_shiny_game.title', $hubGame->getTitle());
        $this->assertSame('img/game/which-shiny.svg', $hubGame->getIcon());
        $this->assertSame('which_shiny_game', $hubGame->getCsrfTokenId());
        $this->assertSame('app_whichshinygame_dex', $hubGame->getDexRoute());
        $this->assertSame('app_whichshinygame_play', $hubGame->getPlayRoute());
        $this->assertSame('app_whichshinygame_create', $hubGame->getCreateRoute());
    }

    private static function makeColorGame(string $id, string $status, string $startedAt, ?string $lastPlayedAt): ColorGame
    {
        return new ColorGame(
            id: $id,
            dex: new ColorGameDexSummary(slug: 'home', name: 'Home', frenchName: 'Home'),
            isShiny: false,
            status: $status,
            roundCount: 5,
            answeredCount: 1,
            correctCount: 1,
            startedAt: $startedAt,
            lastPlayedAt: $lastPlayedAt,
            endedAt: null,
        );
    }
}
