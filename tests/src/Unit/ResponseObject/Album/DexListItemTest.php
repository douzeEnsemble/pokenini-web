<?php

declare(strict_types=1);

namespace App\Tests\Unit\ResponseObject\Album;

use App\ResponseObject\Album\DexFlags;
use App\ResponseObject\Album\DexListItem;
use App\ResponseObject\Album\DexListItemRef;
use App\ResponseObject\Album\DexListItemSettings;
use App\ResponseObject\Album\Report;
use App\ResponseObject\Common\DexCategory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(DexListItem::class)]
final class DexListItemTest extends TestCase
{
    #[Test]
    public function getters(): void
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
        $this->assertNull($item->getReport());
        $this->assertNull($item->getCategory());
    }

    #[Test]
    public function gettersWithReport(): void
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
        $report = new Report(total: 151, totalCaught: 42, totalUncaught: 109, detail: []);

        $category = new DexCategory('main', 'Main games', 'Jeux principaux', 1);

        $item = new DexListItem(dex: $ref, settings: $settings, flags: $flags, report: $report, category: $category);

        $this->assertSame($report, $item->getReport());
        $this->assertSame($category, $item->getCategory());
    }
}
