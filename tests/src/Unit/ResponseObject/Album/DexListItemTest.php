<?php

declare(strict_types=1);

namespace App\Tests\Unit\ResponseObject\Album;

use App\ResponseObject\Album\DexFlags;
use App\ResponseObject\Album\DexListItem;
use App\ResponseObject\Album\DexListItemRef;
use App\ResponseObject\Album\DexListItemSettings;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(DexListItem::class)]
final class DexListItemTest extends TestCase
{
    public function testGetters(): void
    {
        $ref = new DexListItemRef(slug: 'swordshield');
        $settings = new DexListItemSettings(
            name: 'Sword, Shield',
            frenchName: 'Épée, Bouclier',
            slug: 'swordshield',
            displayTemplate: 'box',
        );
        $flags = new DexFlags(
            isShiny: false,
            isPrivate: false,
            isOnHome: true,
            isDisplayForm: true,
            isReleased: true,
            isPremium: false,
            isCustom: false,
        );

        $item = new DexListItem(dex: $ref, settings: $settings, flags: $flags);

        $this->assertSame($ref, $item->getDex());
        $this->assertSame($settings, $item->getSettings());
        $this->assertSame($flags, $item->getFlags());
    }
}
