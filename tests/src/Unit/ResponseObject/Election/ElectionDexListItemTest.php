<?php

declare(strict_types=1);

namespace App\Tests\Unit\ResponseObject\Election;

use App\ResponseObject\Album\DexFlags;
use App\ResponseObject\Common\DexCategory;
use App\ResponseObject\Election\ElectionDexListItem;
use App\ResponseObject\Election\ElectionReport;
use App\ResponseObject\Election\ElectionReportCompletion;
use App\ResponseObject\Election\ElectionReportMetrics;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(ElectionDexListItem::class)]
final class ElectionDexListItemTest extends TestCase
{
    #[Test]
    public function getters(): void
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
            originalSlug: 'swordshield',
            name: 'Sword, Shield',
            frenchName: 'Épée, Bouclier',
            flags: $flags,
            displayTemplate: 'box',
            description: 'A description',
            frenchDescription: 'Une description',
            dexTotalCount: 832,
        );

        $this->assertSame('swordshield', $item->getSlug());
        $this->assertSame('swordshield', $item->getOriginalSlug());
        $this->assertSame('Sword, Shield', $item->getName());
        $this->assertSame('Épée, Bouclier', $item->getFrenchName());
        $this->assertSame(['en' => 'Sword, Shield', 'fr' => 'Épée, Bouclier'], $item->getNames());
        $this->assertSame($flags, $item->getFlags());
        $this->assertSame('box', $item->getDisplayTemplate());
        $this->assertSame('A description', $item->getDescription());
        $this->assertSame('Une description', $item->getFrenchDescription());
        $this->assertSame(['en' => 'A description', 'fr' => 'Une description'], $item->getDescriptions());
        $this->assertSame(832, $item->getDexTotalCount());
        $this->assertNull($item->getReport());
        $this->assertNull($item->getCategory());
    }

    #[Test]
    public function nullableGetters(): void
    {
        $item = new ElectionDexListItem(
            slug: 'test',
            originalSlug: 'test',
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
        $this->assertSame(['*' => null], $item->getDescriptions());
        $this->assertNull($item->getDexTotalCount());
        $this->assertNull($item->getReport());
    }

    #[Test]
    public function gettersWithReport(): void
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
        $report = new ElectionReport(
            metrics: new ElectionReportMetrics(
                completion: new ElectionReportCompletion(atMaxCount: 5, underMaxCount: 1),
                dexTotalCount: 48,
                roundCount: 7,
                totalRoundCount: 8,
            ),
        );

        $item = new ElectionDexListItem(
            slug: 'swordshield',
            originalSlug: 'swordshield',
            name: 'Sword, Shield',
            frenchName: 'Épée, Bouclier',
            flags: $flags,
            displayTemplate: 'box',
            description: null,
            frenchDescription: null,
            dexTotalCount: null,
            report: $report,
        );

        $this->assertSame($report, $item->getReport());
    }

    #[Test]
    public function category(): void
    {
        $category = new DexCategory('home', 'Pokémon Home', 'Pokémon Home', 2);
        $item = new ElectionDexListItem(
            slug: 'home',
            originalSlug: 'home',
            name: 'Home',
            frenchName: 'Home',
            flags: new DexFlags(
                isShiny: false,
                isPrivate: false,
                isOnHome: true,
                isDisplayForm: true,
                isReleased: true,
                isPremium: false,
                isCustom: false,
            ),
            displayTemplate: null,
            description: null,
            frenchDescription: null,
            dexTotalCount: null,
            category: $category,
        );

        $this->assertSame($category, $item->getCategory());
    }

    #[Test]
    public function inASingleLanguage(): void
    {
        $item = new ElectionDexListItem(
            slug: 'test',
            originalSlug: 'test',
            name: 'Démo',
            frenchName: null,
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
            description: 'Description',
            frenchDescription: null,
            dexTotalCount: null,
        );

        $this->assertNull($item->getFrenchName());
        $this->assertSame(['*' => 'Démo'], $item->getNames());
        $this->assertSame(['*' => 'Description'], $item->getDescriptions());
    }
}
