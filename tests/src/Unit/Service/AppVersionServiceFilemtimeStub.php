<?php

declare(strict_types=1);

namespace App\Service;

use App\Tests\Unit\Service\AppVersionServiceTest;

/**
 * Shadows the global filemtime() inside App\Service (PHP falls back to the global
 * function for unqualified calls, so this only intercepts calls from that namespace).
 * Lets tests force the TOCTOU race in AppVersionService::getUpdatedAt() — file exists,
 * then filemtime() fails — without an actual race condition. Also counts calls so tests
 * can prove the missing-file guard clause returns early instead of falling through to
 * filemtime() (whose failure on a missing path would otherwise mask the removed guard).
 */
function filemtime(string $filename): false|int
{
    ++AppVersionServiceTest::$filemtimeCallCount;

    return AppVersionServiceTest::$forceFilemtimeFailure ? false : \filemtime($filename);
}
