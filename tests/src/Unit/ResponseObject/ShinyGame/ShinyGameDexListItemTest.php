<?php

declare(strict_types=1);

namespace App\Tests\Unit\ResponseObject\ShinyGame;

use App\ResponseObject\ShinyGame\ShinyGame;
use App\ResponseObject\ShinyGame\ShinyGameDexListItem;
use App\ResponseObject\ShinyGame\ShinyGameDexSummary;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(ShinyGameDexListItem::class)]
final class ShinyGameDexListItemTest extends TestCase
{
    #[Test]
    public function getters(): void
    {
        $game = new ShinyGame('g1', new ShinyGameDexSummary('demo', 'Demo', 'Démo'), 'in_progress', 3, 1, 1, '2026-10-20T10:00:00+00:00', null, null);
        $object = new ShinyGameDexListItem('demo', 'Demo', 'Démo', false, true, 'Description', 'Description fr', 4, $game);

        $this->assertSame('demo', $object->getSlug());
        $this->assertSame('Demo', $object->getName());
        $this->assertSame('Démo', $object->getFrenchName());
        $this->assertSame(false, $object->isReleased());
        $this->assertSame(true, $object->isPremium());
        $this->assertSame('Description', $object->getDescription());
        $this->assertSame('Description fr', $object->getFrenchDescription());
        $this->assertSame(4, $object->getPlayableCount());
        $this->assertSame($game, $object->getCurrentGame());
    }
}
