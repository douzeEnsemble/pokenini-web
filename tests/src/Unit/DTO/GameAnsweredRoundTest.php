<?php

declare(strict_types=1);

namespace App\Tests\Unit\DTO;

use App\DTO\GameAnsweredRound;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(GameAnsweredRound::class)]
final class GameAnsweredRoundTest extends TestCase
{
    #[Test]
    public function properties(): void
    {
        $round = new \stdClass();
        $answer = new \stdClass();

        $answered = new GameAnsweredRound('douze', 2, $round, $answer);

        $this->assertSame('douze', $answered->gameId);
        $this->assertSame(2, $answered->position);
        $this->assertSame($round, $answered->round);
        $this->assertSame($answer, $answered->answer);
    }

    #[Test]
    #[DataProvider('providerIsFor')]
    public function isFor(string $gameId, int $position, bool $expected): void
    {
        $answered = new GameAnsweredRound('douze', 2, new \stdClass(), new \stdClass());

        $this->assertSame($expected, $answered->isFor($gameId, $position));
    }

    /**
     * @return array<string, array{string, int, bool}>
     */
    public static function providerIsFor(): array
    {
        return [
            'same round' => ['douze', 2, true],
            'another round' => ['douze', 3, false],
            'another game' => ['treize', 2, false],
            'another game and round' => ['treize', 3, false],
        ];
    }
}
