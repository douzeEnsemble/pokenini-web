<?php

declare(strict_types=1);

namespace App\Tests\Unit\DTO;

use App\DTO\TrainerDexLinkEdge;
use App\ResponseObject\Album\DexFlags;
use App\ResponseObject\Album\DexListItem;
use App\ResponseObject\Album\DexListItemRef;
use App\ResponseObject\Album\DexListItemSettings;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(TrainerDexLinkEdge::class)]
final class TrainerDexLinkEdgeTest extends TestCase
{
    public function testGetters(): void
    {
        $from = $this->createDexListItem('swordshield');
        $toDex = $this->createDexListItem('scarletviolet');

        $edge = new TrainerDexLinkEdge('edge-1', 'both', $from, $toDex);

        $this->assertSame('edge-1', $edge->getId());
        $this->assertSame('both', $edge->getMode());
        $this->assertSame($from, $edge->getFrom());
        $this->assertSame($toDex, $edge->getTo());
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
