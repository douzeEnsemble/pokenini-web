<?php

declare(strict_types=1);

namespace App\Utils;

final class JsonDecoder
{
    public static function decode(string $json): mixed
    {
        return json_decode($json, true, 5, JSON_THROW_ON_ERROR);
    }
}
