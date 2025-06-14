<?php

declare(strict_types=1);

namespace App\Cache;

class KeyMaker
{
    private const string CACHE_KEY_SEPARATOR = '_';
    private const string CACHE_KEY_ID_SEPARATOR = '#';

    private const string CACHE_KEY_DEX = 'dex';
    private const string CACHE_KEY_ELECTION_DEX = 'election_dex';
    private const string CACHE_KEY_CATCH_STATES = 'catch_states';
    private const string CACHE_KEY_TYPES = 'types';
    private const string CACHE_KEY_GAME_BUNDLES = 'game_bundles';
    private const string CACHE_KEY_COLLECTIONS = 'collections';
    private const string CACHE_KEY_FORMS_CATEGORY = 'forms_category';
    private const string CACHE_KEY_FORMS_REGIONAL = 'forms_regional';
    private const string CACHE_KEY_FORMS_SPECIAL = 'forms_special';
    private const string CACHE_KEY_FORMS_VARIANT = 'forms_variant';
    private const string CACHE_KEY_ALBUM = 'album';
    private const string CACHE_KEY_REPORTS = 'reports';
    private const string CACHE_KEY_TRAINER = 'trainer';

    public static function getDexKey(): string
    {
        return self::CACHE_KEY_DEX;
    }

    public static function getCatchStatesKey(): string
    {
        return self::CACHE_KEY_CATCH_STATES;
    }

    public static function getTypesKey(): string
    {
        return self::CACHE_KEY_TYPES;
    }

    public static function getGameBundlesKey(): string
    {
        return self::CACHE_KEY_GAME_BUNDLES;
    }

    public static function getCollectionsKey(): string
    {
        return self::CACHE_KEY_COLLECTIONS;
    }

    public static function getFormsCategoryKey(): string
    {
        return self::CACHE_KEY_FORMS_CATEGORY;
    }

    public static function getFormsRegionalKey(): string
    {
        return self::CACHE_KEY_FORMS_REGIONAL;
    }

    public static function getFormsSpecialKey(): string
    {
        return self::CACHE_KEY_FORMS_SPECIAL;
    }

    public static function getFormsVariantKey(): string
    {
        return self::CACHE_KEY_FORMS_VARIANT;
    }

    public static function getAlbumKey(): string
    {
        return self::CACHE_KEY_ALBUM;
    }

    public static function getReportsKey(): string
    {
        return self::CACHE_KEY_REPORTS;
    }

    /**
     * @param string[] $queryParams
     */
    public static function getDexKeyForTrainer(string $trainerId, array $queryParams = []): string
    {
        $cacheKeySuffixe = http_build_query($queryParams, '', self::CACHE_KEY_SEPARATOR);

        return self::CACHE_KEY_DEX.self::CACHE_KEY_SEPARATOR.$trainerId
            .($cacheKeySuffixe ? self::CACHE_KEY_SEPARATOR.$cacheKeySuffixe : '');
    }

    /**
     * @param string[] $queryParams
     */
    public static function getElectionDexKey(array $queryParams = []): string
    {
        $cacheKeySuffixe = http_build_query($queryParams, '', self::CACHE_KEY_SEPARATOR);

        return self::CACHE_KEY_ELECTION_DEX.($cacheKeySuffixe ? self::CACHE_KEY_SEPARATOR.$cacheKeySuffixe : '');
    }

    /**
     * @param string[]|string[][] $filters
     */
    public static function getPokedexKey(string $dexSlug, string $trainerId, array $filters = []): string
    {
        $prefix = self::CACHE_KEY_ALBUM
            .self::CACHE_KEY_SEPARATOR.$dexSlug
            .self::CACHE_KEY_SEPARATOR.$trainerId;

        $strFilters = '';
        foreach ($filters as $key => $value) {
            if (!is_array($value)) {
                $strFilters .= self::CACHE_KEY_SEPARATOR.$key.$value;

                continue;
            }

            foreach ($value as $subValue) {
                $strFilters .= self::CACHE_KEY_SEPARATOR.$key.$subValue;
            }
        }

        return $prefix.$strFilters;
    }

    public static function getTrainerIdKey(string $trainerId): string
    {
        return self::CACHE_KEY_TRAINER.self::CACHE_KEY_ID_SEPARATOR.$trainerId;
    }
}
