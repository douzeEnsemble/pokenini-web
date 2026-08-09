<?php

declare(strict_types=1);

namespace App\Tests\Unit\DTO;

use App\DTO\TrainerDexLinkEdge;
use App\DTO\TrainerDexLinksTree;
use App\ResponseObject\Album\DexFlags;
use App\ResponseObject\Album\DexListItem;
use App\ResponseObject\Album\DexListItemRef;
use App\ResponseObject\Album\DexListItemSettings;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(TrainerDexLinksTree::class)]
final class TrainerDexLinksTreeTest extends TestCase
{
    public function testIsEmptyWithNoEdges(): void
    {
        $tree = new TrainerDexLinksTree([]);

        $this->assertTrue($tree->isEmpty());
        $this->assertSame([], $tree->getEdges());
    }

    public function testIsNotEmptyWithEdges(): void
    {
        $edge = new TrainerDexLinkEdge(
            'edge-1',
            'to',
            $this->createDexListItem('swordshield'),
            $this->createDexListItem('scarletviolet'),
        );

        $tree = new TrainerDexLinksTree([$edge]);

        $this->assertFalse($tree->isEmpty());
        $this->assertSame([$edge], $tree->getEdges());
    }

    private function createDexListItem(string $slug): DexListItem
    {
        return new DexListItem(
            dex: new DexListItemRef(slug: $slug),
            settings: new DexListItemSettings(
                name: $slug,
                frenchName: $slug,
                slug: $slug,
                displayTemplate: 'box',
            ),
            flags: new DexFlags(
                isShiny: false,
                isPrivate: false,
                isOnHome: true,
                isDisplayForm: true,
                isReleased: true,
                isPremium: false,
                isCustom: false,
            ),
        );
    }
}
