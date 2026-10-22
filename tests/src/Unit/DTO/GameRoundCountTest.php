<?php

declare(strict_types=1);

namespace App\Tests\Unit\DTO;

use App\DTO\GameRoundCount;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(GameRoundCount::class)]
final class GameRoundCountTest extends TestCase
{
    #[Test]
    #[DataProvider('providerValue')]
    public function value(string $value, ?int $expected): void
    {
        $this->assertSame($expected, (new GameRoundCount($value))->value);
    }

    /**
     * @return array<string, array{string, null|int}>
     */
    public static function providerValue(): array
    {
        return [
            'preset' => ['10', 10],
            'one round' => ['1', 1],
            'custom' => ['37', 37],
            'whole dex' => ['', null],
            'zero' => ['0', null],
            'negative' => ['-3', null],
            'decimal' => ['2.5', null],
            'not a number' => ['all', null],
        ];
    }
}
