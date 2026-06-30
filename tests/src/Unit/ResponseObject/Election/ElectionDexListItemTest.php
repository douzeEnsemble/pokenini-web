<?php

declare(strict_types=1);

namespace App\Tests\Unit\ResponseObject\Election;

use App\ResponseObject\Album\DexFlags;
use App\ResponseObject\Election\ElectionDexListItem;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(ElectionDexListItem::class)]
final class ElectionDexListItemTest extends TestCase
{
    public function testGetters(): void
    {
        $flags = new DexFlags(
            isShiny: false,
            isPrivate: false,
            isOnHome: true,
            isDisplayForm: true,
            isReleased: true,
            isPremium: false,
            isCustom: false,
        );

        $item = new ElectionDexListItem(
            slug: 'swordshield',
            name: 'Sword, Shield',
            frenchName: 'Épée, Bouclier',
            flags: $flags,
            displayTemplate: 'box',
            description: 'A description',
            frenchDescription: 'Une description',
            dexTotalCount: 832,
        );

        $this->assertSame('swordshield', $item->getSlug());
        $this->assertSame('Sword, Shield', $item->getName());
        $this->assertSame('Épée, Bouclier', $item->getFrenchName());
        $this->assertSame($flags, $item->getFlags());
        $this->assertSame('box', $item->getDisplayTemplate());
        $this->assertSame('A description', $item->getDescription());
        $this->assertSame('Une description', $item->getFrenchDescription());
        $this->assertSame(832, $item->getDexTotalCount());
    }

    public function testNullableGetters(): void
    {
        $item = new ElectionDexListItem(
            slug: 'test',
            name: 'Test',
            frenchName: 'Test',
            flags: new DexFlags(
                isShiny: false,
                isPrivate: false,
                isOnHome: false,
                isDisplayForm: false,
                isReleased: false,
                isPremium: false,
                isCustom: false,
            ),
            displayTemplate: null,
            description: null,
            frenchDescription: null,
            dexTotalCount: null,
        );

        $this->assertNull($item->getDisplayTemplate());
        $this->assertNull($item->getDescription());
        $this->assertNull($item->getFrenchDescription());
        $this->assertNull($item->getDexTotalCount());
    }
}
