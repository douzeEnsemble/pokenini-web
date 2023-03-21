<?php

declare(strict_types=1);

namespace App\Cache;

class KeyMaker
{
    private const CACHE_KEY_SEPARATOR = '_';

    private const CACHE_KEY_CACHE_REGISTER = 'register';

    private const CACHE_KEY_DEX = 'dex';
    private const CACHE_KEY_CATCH_STATES = 'catch_states';
    private const CACHE_KEY_ALBUM = 'album';
    private const CACHE_KEY_REPORTS = 'reports';
    private const CACHE_KEY_ACTIONS = 'actions';

    public static function getDexKey(): string
    {
        return self::CACHE_KEY_DEX;
    }

    public static function getCatchStatesKey(): string
    {
        return self::CACHE_KEY_CATCH_STATES;
    }

    public static function getAlbumKey(): string
    {
        return self::CACHE_KEY_ALBUM;
    }

    public static function getReportsKey(): string
    {
        return self::CACHE_KEY_REPORTS;
    }

    public static function getActionsKey(): string
    {
        return self::CACHE_KEY_ACTIONS;
    }

    public static function getDexKeyForTrainer(string $trainerId, string $alt = ''): string
    {
        return self::CACHE_KEY_DEX . self::CACHE_KEY_SEPARATOR . $trainerId . $alt;
    }

    public static function getPokedexKey(string $dexSlug, string $trainerId): string
    {
        return self::CACHE_KEY_ALBUM . self::CACHE_KEY_SEPARATOR . $dexSlug . self::CACHE_KEY_SEPARATOR . $trainerId;
    }

    public static function getRegisterTypeKey(string $type): string
    {
        return self::CACHE_KEY_CACHE_REGISTER . self::CACHE_KEY_SEPARATOR . $type;
    }
}
