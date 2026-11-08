<?php

declare(strict_types=1);

namespace App\ResponseObject\Common;

/**
 * The name of a label indexed by locale, whatever the way the API gave it.
 */
final class NamesMap
{
    /**
     * The key of a name which the API already resolved in the language it was asked for.
     */
    public const string RESOLVED = '*';

    /**
     * @param null|string $frenchName null when the API answered in a single language: `$name` is then
     *                                the name in that language, whichever it is
     *
     * @return array<string, string>
     */
    public static function from(string $name, ?string $frenchName): array
    {
        if (null === $frenchName) {
            return [self::RESOLVED => $name];
        }

        return ['en' => $name, 'fr' => $frenchName];
    }
}
