<?php

namespace App\DTO;

class AlbumMode
{
    public const SHORT_MODE_READ = 'r';
    public const SHORT_MODE_WRITE = 'w';

    public const LONG_MODE_READ = 'read';
    public const LONG_MODE_WRITE = 'write';

    // Short version to long version
    public const MODES_SHORT_LONG = [
        self::SHORT_MODE_READ => self::LONG_MODE_READ,
        self::SHORT_MODE_WRITE => self::LONG_MODE_WRITE,
    ];

    // Long version to short version
    public const MODES_LONG_SHORT = [
        self::LONG_MODE_READ => self::SHORT_MODE_READ,
        self::LONG_MODE_WRITE => self::SHORT_MODE_WRITE,
    ];

    // Opposite mode to switch
    public const MODES_LONG_OPPOSITE = [
        self::LONG_MODE_READ => self::LONG_MODE_WRITE,
        self::LONG_MODE_WRITE => self::LONG_MODE_READ,
    ];
}
