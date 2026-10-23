<?php

declare(strict_types=1);

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Grid of the answered rounds of a game, to share it.
 */
final class GameExtension extends AbstractExtension
{
    private const string CORRECT = '🟩';
    private const string HALF = '🟨';
    private const string WRONG = '🟥';

    /** Up to this number of rounds, one square per round, ten per line */
    private const int FULL_GRID_MAX = 50;

    /** Beyond, one line of this number of squares, each one summing up a part of the rounds */
    private const int SUMMARY_SIZE = 10;

    #[\Override]
    public function getFunctions(): array
    {
        return [
            new TwigFunction('game_results_grid', [$this, 'getResultsGrid']),
        ];
    }

    /**
     * @param list<bool> $results answered rounds results, in the rounds order
     */
    public function getResultsGrid(array $results): string
    {
        $count = count($results);

        if ($count > self::FULL_GRID_MAX) {
            return $this->getSummaryGrid($results, $count);
        }

        $lines = [];
        foreach (array_chunk($results, 10) as $line) {
            $lines[] = implode('', array_map(
                static fn (bool $result): string => $result ? self::CORRECT : self::WRONG,
                $line,
            ));
        }

        return implode("\n", $lines);
    }

    /**
     * A square is green from 80 % of right answers in its part of the rounds, yellow from 50 %, red below.
     *
     * @param list<bool> $results
     */
    private function getSummaryGrid(array $results, int $count): string
    {
        $squares = '';
        for ($part = 0; $part < self::SUMMARY_SIZE; ++$part) {
            $start = intdiv($part * $count, self::SUMMARY_SIZE);
            $end = intdiv(($part + 1) * $count, self::SUMMARY_SIZE);
            $partResults = array_slice($results, $start, $end - $start);

            $squares .= $this->getSummarySquare(count(array_filter($partResults)), count($partResults));
        }

        return $squares;
    }

    private function getSummarySquare(int $correct, int $total): string
    {
        if ($correct * 10 >= $total * 8) {
            return self::CORRECT;
        }

        if ($correct * 10 >= $total * 5) {
            return self::HALF;
        }

        return self::WRONG;
    }
}
