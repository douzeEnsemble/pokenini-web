<?php

declare(strict_types=1);

namespace App\Tests\Unit\ResponseObject\WhichShinyGame;

use App\ResponseObject\Common\DexCategory;
use App\ResponseObject\WhichShinyGame\WhichShinyGame;
use App\ResponseObject\WhichShinyGame\WhichShinyGameDexListItem;
use App\ResponseObject\WhichShinyGame\WhichShinyGameDexSummary;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(WhichShinyGameDexListItem::class)]
final class WhichShinyGameDexListItemTest extends TestCase
{
    #[Test]
    public function getters(): void
    {
        $game = new WhichShinyGame('g1', new WhichShinyGameDexSummary('demo', 'Demo', 'Démo'), 'in_progress', 3, 1, 1, '2026-10-20T10:00:00+00:00', null, null);
        $category = new DexCategory('home', 'Pokémon Home', 'Pokémon Home', 2);
        $object = new WhichShinyGameDexListItem('demo', 'Demo', 'Démo', false, true, 'Description', 'Description fr', 4, $game, $category);

        $this->assertSame('demo', $object->getSlug());
        $this->assertSame('Demo', $object->getName());
        $this->assertSame('Démo', $object->getFrenchName());
        $this->assertSame(false, $object->isReleased());
        $this->assertSame(true, $object->isPremium());
        $this->assertSame('Description', $object->getDescription());
        $this->assertSame('Description fr', $object->getFrenchDescription());
        $this->assertSame(4, $object->getPlayableCount());
        $this->assertSame($game, $object->getCurrentGame());
        $this->assertSame($category, $object->getCategory());
    }
}
