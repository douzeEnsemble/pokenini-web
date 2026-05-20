<?php

declare(strict_types=1);

namespace App\Tests\Unit\AlbumFilters;

use App\AlbumFilters\AlbumFilterBag;
use App\AlbumFilters\FromRequest;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

/**
 * @internal
 */
#[CoversClass(FromRequest::class)]
final class FromRequestTest extends TestCase
{
    public function testGet(): void
    {
        $request = new Request([
            'cs' => 'no',
            'f' => 'pichu',
            'fc' => ['cat1', 'cat2', null],
            'fr' => ['reg1', 'reg2'],
            'fs' => ['spe1', 'spe2'],
            'fv' => ['var1', null, 'var2'],
            'at' => ['typ-a.1', 'type-a.2'],
            't1' => ['typ1.1', 'type1.2'],
            't2' => ['typ2.1', 'type2.2'],
            'ogb' => ['ogb1', 'ogb2'],
            'gba' => ['gba1', 'gba2'],
            'gbsa' => ['gbsa1', 'gbsa2'],
            'ca' => ['ca1', 'ca2'],
        ]);

        $bag = FromRequest::get($request);

        $this->assertEquals(
            ['cs' => 'no', 'f' => 'pichu'],
            $bag->stringFilters,
        );
        $this->assertEquals(
            [
                'fc' => ['cat1', 'cat2'],
                'fr' => ['reg1', 'reg2'],
                'fs' => ['spe1', 'spe2'],
                'fv' => ['var1', 'var2'],
                'at' => ['typ-a.1', 'type-a.2'],
                't1' => ['typ1.1', 'type1.2'],
                't2' => ['typ2.1', 'type2.2'],
                'ogb' => ['ogb1', 'ogb2'],
                'gba' => ['gba1', 'gba2'],
                'gbsa' => ['gbsa1', 'gbsa2'],
                'ca' => ['ca1', 'ca2'],
            ],
            $bag->multipleFilters,
        );
    }

    public function testGetWithNegatives(): void
    {
        $request = new Request([
            'cs' => '!no',
            'f' => 'pichu',
            'ogb' => ['ogb1', 'ogb2'],
            'gba' => ['gba1', 'gba2', '!gba3'],
            'gbsa' => ['gbsa1', 'gbsa2', '!gbsa3'],
            'ca' => ['ca1', '!ca2'],
        ]);

        $bag = FromRequest::get($request);

        $this->assertEquals(['cs' => '!no', 'f' => 'pichu'], $bag->stringFilters);
        $this->assertEquals(
            [
                'ogb' => ['ogb1', 'ogb2'],
                'gba' => ['gba1', 'gba2', '!gba3'],
                'gbsa' => ['gbsa1', 'gbsa2', '!gbsa3'],
                'ca' => ['ca1', '!ca2'],
            ],
            $bag->multipleFilters,
        );
    }

    public function testGetEmptyRequest(): void
    {
        $bag = FromRequest::get(new Request());

        $this->assertEquals(new AlbumFilterBag(), $bag);
    }
}
