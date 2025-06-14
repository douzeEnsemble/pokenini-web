<?php

declare(strict_types=1);

namespace App\Tests\Unit\AlbumFilters;

use App\AlbumFilters\Mapping;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(Mapping::class)]
class MappingTest extends TestCase
{
    public function testGet(): void
    {
        $this->assertEquals(
            self::getExpectedData(),
            Mapping::get(self::getMappingData()),
        );
    }

    /**
     * @return string[]|string[][]
     */
    public static function getMappingData(): array
    {
        return [
            'cs' => 'no',
            'f' => 'pichu',
            'fc' => [
                'cat1',
                'cat2',
            ],
            'fr' => [
                'reg1',
                'reg2',
            ],
            'fs' => [
                'spe1',
                'spe2',
            ],
            'fv' => [
                'var1',
                'var2',
            ],
            'at' => [
                'typ-a.1',
                'type-a.2',
            ],
            't1' => [
                'typ1.1',
                'type1.2',
            ],
            't2' => [
                'typ2.1',
                'type2.2',
            ],
            'ogb' => [
                'ogb1',
                'ogb2',
            ],
            'gba' => [
                'gba1',
                'gba2',
                '!gba3',
            ],
            'gbsa' => [
                'gbsa1',
                'gbsa2',
                '!gbsa3',
            ],
            'ca' => [
                'ca1',
                '!ca2',
            ],
        ];
    }

    /**
     * @return string[][]
     */
    public static function getExpectedData(): array
    {
        return [
            'catch_states' => [
                'no',
            ],
            'families' => [
                'pichu',
            ],
            'category_forms' => [
                'cat1',
                'cat2',
            ],
            'regional_forms' => [
                'reg1',
                'reg2',
            ],
            'special_forms' => [
                'spe1',
                'spe2',
            ],
            'variant_forms' => [
                'var1',
                'var2',
            ],
            'any_types' => [
                'typ-a.1',
                'type-a.2',
            ],
            'primary_types' => [
                'typ1.1',
                'type1.2',
            ],
            'secondary_types' => [
                'typ2.1',
                'type2.2',
            ],
            'original_game_bundles' => [
                'ogb1',
                'ogb2',
            ],
            'game_bundle_availabilities' => [
                'gba1',
                'gba2',
                '!gba3',
            ],
            'game_bundle_shiny_availabilities' => [
                'gbsa1',
                'gbsa2',
                '!gbsa3',
            ],
            'collection_availabilities' => [
                'ca1',
                '!ca2',
            ],
        ];
    }
}
