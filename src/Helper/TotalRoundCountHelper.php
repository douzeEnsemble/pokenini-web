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

        $perViewCountFloat = (float) $perViewCount;

        $totalScreens = 0.0;
        $currentCount = (float) $dexTotalCount;

        while ($currentCount > 0) {
            $screensInCurrentRound = ceil($currentCount / $perViewCountFloat);
            $totalScreens += $screensInCurrentRound;

            $currentCount = round(
                (floor($currentCount / $perViewCountFloat) * $winnerAverage)
                + ((float) ($currentCount % $perViewCountFloat) / ($perViewCountFloat / $winnerAverage))
            );

            if (round($winnerAverage) >= $currentCount) {
                // +1 because of the screen when you have the last ONE
                $totalScreens += 1.0;

                break;
            }
        }

        return (int) $totalScreens;
    }
}
