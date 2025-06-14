<?php

declare(strict_types=1);

namespace App\Helper;

final class TotalRoundCountHelper
{
    public static function calculate(
        int $dexTotalCount,
        int $perViewCount,
        float $winnerAverage,
    ): int {
        if (0 === $perViewCount) {
            throw new \InvalidArgumentException("perViewCount can't be egals to 0");
        }

        if (0.0 === $winnerAverage) {
            return 0;
        }

        $totalScreens = 0;
        $currentCount = $dexTotalCount;

        while ($currentCount > 0) {
            $screensInCurrentRound = ceil($currentCount / $perViewCount);
            $totalScreens += $screensInCurrentRound;

            $currentCount = round(
                (floor($currentCount / $perViewCount) * $winnerAverage)
                + (($currentCount % $perViewCount) / ($perViewCount / $winnerAverage))
            );

            if (round($winnerAverage) >= $currentCount) {
                // +1 because of the screen when you have the last ONE
                ++$totalScreens;

                break;
            }
        }

        return (int) $totalScreens;
    }
}
