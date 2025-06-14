<?php

declare(strict_types=1);

namespace App\Tests\Unit\Cache;

use App\Cache\KeyMaker;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(KeyMaker::class)]
class KeyMakerTest extends TestCase
{
    public function testGetDexKey(): void
    {
        $this->assertEquals('dex', KeyMaker::getDexKey());
    }

    public function testGetCatchStatesKey(): void
    {
        $this->assertEquals('catch_states', KeyMaker::getCatchStatesKey());
    }

    public function testGetTypesKey(): void
    {
        $this->assertEquals('types', KeyMaker::getTypesKey());
    }

    public function testGetGameBundlesKey(): void
    {
        $this->assertEquals('game_bundles', KeyMaker::getGameBundlesKey());
    }

    public function testGetCollectionsKey(): void
    {
        $this->assertEquals('collections', KeyMaker::getCollectionsKey());
    }

    public function testGetFormsCategoryKey(): void
    {
        $this->assertEquals('forms_category', KeyMaker::getFormsCategoryKey());
    }

    public function testGetFormsRegionalKey(): void
    {
        $this->assertEquals('forms_regional', KeyMaker::getFormsRegionalKey());
    }

    public function testGetFormsSpecialKey(): void
    {
        $this->assertEquals('forms_special', KeyMaker::getFormsSpecialKey());
    }

    public function testGetFormsVariantKey(): void
    {
        $this->assertEquals('forms_variant', KeyMaker::getFormsVariantKey());
    }

    public function testGetAlbumKey(): void
    {
        $this->assertEquals('album', KeyMaker::getAlbumKey());
    }

    public function testGetReportsKey(): void
    {
        $this->assertEquals('reports', KeyMaker::getReportsKey());
    }

    public function testGetDexKeyForTrainer(): void
    {
        $this->assertEquals('dex_1', KeyMaker::getDexKeyForTrainer('1'));
        $this->assertEquals('dex_12', KeyMaker::getDexKeyForTrainer('12'));
    }

    public function testGetDexKeyForTrainerWithQueryParams(): void
    {
        $this->assertEquals('dex_1_1=1', KeyMaker::getDexKeyForTrainer('1', ['1' => '1']));
        $this->assertEquals('dex_12_1=1_2=2', KeyMaker::getDexKeyForTrainer('12', ['1' => '1', '2' => '2']));
    }

    public function testGetElectionDexKey(): void
    {
        $this->assertEquals('election_dex', KeyMaker::getElectionDexKey());
    }

    public function testGetElectionDexKeyWithQueryParams(): void
    {
        $this->assertEquals('election_dex_1=1', KeyMaker::getElectionDexKey(['1' => '1']));
        $this->assertEquals('election_dex_1=1_2=2', KeyMaker::getElectionDexKey(['1' => '1', '2' => '2']));
    }

    public function testGetPokedexKey(): void
    {
        $this->assertEquals('album_douze_12', KeyMaker::getPokedexKey('douze', '12'));
        $this->assertEquals('album_toto_0', KeyMaker::getPokedexKey('toto', '0'));
        $this->assertEquals(
            'album_toto_0_csno_fpichu',
            KeyMaker::getPokedexKey(
                'toto',
                '0',
                [
                    'cs' => 'no',
                    'f' => 'pichu',
                ],
            )
        );
        $this->assertEquals(
            'album_toto_0_fcun_fcdos_fctres',
            KeyMaker::getPokedexKey(
                'toto',
                '0',
                [
                    'fc' => [
                        'un',
                        'dos',
                        'tres',
                    ],
                ],
            )
        );
        $this->assertEquals(
            'album_toto_0_fcun_fcdos_fctres_t1normal_t1water',
            KeyMaker::getPokedexKey(
                'toto',
                '0',
                [
                    'fc' => [
                        'un',
                        'dos',
                        'tres',
                    ],
                    't1' => [
                        'normal',
                        'water',
                    ],
                ],
            )
        );
    }

    public function testGetTrainerIdKey(): void
    {
        $this->assertEquals('trainer#123', KeyMaker::getTrainerIdKey('123'));
        $this->assertEquals('trainer#1654da64faeg54a6f4a8', KeyMaker::getTrainerIdKey('1654da64faeg54a6f4a8'));
    }
}
