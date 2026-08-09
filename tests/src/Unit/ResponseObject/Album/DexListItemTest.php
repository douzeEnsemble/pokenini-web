<?php

declare(strict_types=1);

namespace App\Tests\Unit\ResponseObject\Album;

use App\ResponseObject\Album\DexFlags;
use App\ResponseObject\Album\DexListItem;
use App\ResponseObject\Album\DexListItemRef;
use App\ResponseObject\Album\DexListItemSettings;
use App\ResponseObject\Album\Report;
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

        $item = new DexListItem(dex: $ref, settings: $settings, flags: $flags, report: $report);

        $this->assertSame($report, $item->getReport());
    }
}
