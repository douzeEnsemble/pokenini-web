<?php

declare(strict_types=1);

namespace App\Utils;

final class JsonDecoder
{
    public static function decode(string $json): mixed
    {
        // 5 authorizes 4 levels of container nesting — the maximum depth of current API payloads (root → pokedex → pokemons[] → pokemon{})
        return json_decode($json, true, 5, JSON_THROW_ON_ERROR);
    }
}
