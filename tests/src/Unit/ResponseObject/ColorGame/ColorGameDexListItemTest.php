<?php

declare(strict_types=1);

namespace App\Tests\Unit\ResponseObject\ColorGame;

use App\ResponseObject\ColorGame\ColorGame;
use App\ResponseObject\ColorGame\ColorGameDexListItem;
use App\ResponseObject\ColorGame\ColorGameDexSummary;
use App\ResponseObject\Common\DexCategory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(ColorGameDexListItem::class)]
final class ColorGameDexListItemTest extends TestCase
{
    #[Test]
    public function getters(): void
    {
        $game = new ColorGame('g1', new ColorGameDexSummary('demo', 'Demo', 'Démo'), false, 'in_progress', 3, 1, 1, '2026-10-20T10:00:00+00:00', null, null);
        $category = new DexCategory('home', 'Pokémon Home', 'Pokémon Home', 2);
        $object = new ColorGameDexListItem('demo', 'Demo', 'Démo', true, false, true, 'Description', 'Description fr', 4, $game, $category);

        $this->assertSame('demo', $object->getSlug());
        $this->assertSame('Demo', $object->getName());
        $this->assertSame('Démo', $object->getFrenchName());
        $this->assertSame(['en' => 'Demo', 'fr' => 'Démo'], $object->getNames());
        $this->assertSame(true, $object->isShiny());
        $this->assertSame(false, $object->isReleased());
        $this->assertSame(true, $object->isPremium());
        $this->assertSame('Description', $object->getDescription());
        $this->assertSame('Description fr', $object->getFrenchDescription());
        $this->assertSame(['en' => 'Description', 'fr' => 'Description fr'], $object->getDescriptions());
        $this->assertSame(4, $object->getPlayableCount());
        $this->assertSame($game, $object->getCurrentGame());
        $this->assertSame($category, $object->getCategory());
    }

    #[Test]
    public function inASingleLanguage(): void
    {
        $object = new ColorGameDexListItem('demo', 'Démo', null, false, true, false, 'Description', null, 4, null);

        $this->assertNull($object->getFrenchName());
        $this->assertSame(['*' => 'Démo'], $object->getNames());
        $this->assertNull($object->getFrenchDescription());
        $this->assertSame(['*' => 'Description'], $object->getDescriptions());
    }
}
