<?php

declare(strict_types=1);

namespace App\Utils;

final class JsonDecoder
{
    /**
     * The default depth, 5, authorizes 4 levels of container nesting — the maximum depth of most API payloads (root → pokedex → pokemons[] → pokemon{}).
     * A payload known to be deeper passes its own depth.
     *
     * @param int<1, 2147483647> $depth
     */
    public static function decode(string $json, int $depth = 5): mixed
    {
        return json_decode($json, true, $depth, JSON_THROW_ON_ERROR);
    }
}
