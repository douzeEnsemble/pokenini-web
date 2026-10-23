<?php

declare(strict_types=1);

namespace App\Tests\Unit\Twig;

use App\Twig\GameExtension;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Twig\TwigFunction;

/**
 * @internal
 */
#[CoversClass(GameExtension::class)]
final class GameExtensionTest extends TestCase
{
    #[Test]
    public function getFunctions(): void
    {
        $extension = new GameExtension();

        $functions = $extension->getFunctions();

        $this->assertCount(1, $functions);
        $this->assertInstanceOf(TwigFunction::class, $functions[0]);
        $this->assertSame('game_results_grid', $functions[0]->getName());
        $this->assertSame([$extension, 'getResultsGrid'], $functions[0]->getCallable());
    }

    /**
     * @param list<bool> $results
     */
    #[Test]
    #[DataProvider('providerGetResultsGrid')]
    public function getResultsGrid(array $results, string $expected): void
    {
        $this->assertSame($expected, new GameExtension()->getResultsGrid($results));
    }

    /**
     * @return array<string, array{list<bool>, string}>
     */
    public static function providerGetResultsGrid(): array
    {
        return [
            'no round' => [[], ''],
            'a few rounds' => [[true, false, true], '🟩🟥🟩'],
            'ten rounds on one line' => [
                [true, true, true, true, true, true, true, true, true, false],
                '🟩🟩🟩🟩🟩🟩🟩🟩🟩🟥',
            ],
            'eleven rounds on two lines' => [
                [true, true, true, true, true, true, true, true, true, false, false],
                "🟩🟩🟩🟩🟩🟩🟩🟩🟩🟥\n🟥",
            ],
            'fifty rounds on five lines' => [
                array_fill(0, 50, true),
                implode("\n", array_fill(0, 5, '🟩🟩🟩🟩🟩🟩🟩🟩🟩🟩')),
            ],
            'a hundred rounds summed up by ten' => [
                [
                    ...self::part(10, 10),
                    ...self::part(8, 10),
                    ...self::part(7, 10),
                    ...self::part(5, 10),
                    ...self::part(4, 10),
                    ...self::part(0, 10),
                    ...self::part(9, 10),
                    ...self::part(6, 10),
                    ...self::part(3, 10),
                    ...self::part(1, 10),
                ],
                '🟩🟩🟨🟨🟥🟥🟩🟨🟥🟥',
            ],
            'fifty-one rounds summed up in uneven parts' => [
                // Parts of 5 rounds, the last one of 6
                [...array_fill(0, 45, true), ...self::part(4, 6)],
                '🟩🟩🟩🟩🟩🟩🟩🟩🟩🟨',
            ],
        ];
    }

    /**
     * @return list<bool>
     */
    private static function part(int $correct, int $total): array
    {
        return [...array_fill(0, $correct, true), ...array_fill(0, $total - $correct, false)];
    }
}
