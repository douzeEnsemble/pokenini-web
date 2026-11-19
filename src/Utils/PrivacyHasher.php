<?php

declare(strict_types=1);

namespace App\Utils;

/**
 * Hash used for privacy only (not for security): it avoids exposing a raw identifier (user id, cookie value…).
 * It must stay stable, as the same hash is computed by the other apps and expected in the test fixtures.
 * Never use it for passwords, tokens or anything an attacker could benefit from.
 */
final class PrivacyHasher
{
    public static function hash(string $value): string
    {
        return sha1($value);
    }
}
