<?php

declare(strict_types=1);

namespace App\Helper;

final class PokeniniTokenHelper
{
    public static function getFromDexSlug(string $dexName): string
    {
        $key = "**pokenini**-$dexName-";

        return md5($key);
    }
}
