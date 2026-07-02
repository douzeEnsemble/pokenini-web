<?php

declare(strict_types=1);

namespace App\Tests\Unit\ResponseObject\Common;

use App\ResponseObject\Common\GameBundlesGroup;
use App\ResponseObject\Common\PokemonSlugRef;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(GameBundlesGroup::class)]
final class GameBundlesGroupTest extends TestCase
{
    public function testGetters(): void
    {
        $normal1 = new PokemonSlugRef('xy');
        $shiny1 = new PokemonSlugRef('omegarubyalphasapphire');

        $group = new GameBundlesGroup(normal: [$normal1], shiny: [$shiny1]);

        $this->assertSame([$normal1], $group->getNormal());
        $this->assertSame([$shiny1], $group->getShiny());
    }

    public function testEmptyArrays(): void
    {
        $group = new GameBundlesGroup(normal: [], shiny: []);

        $this->assertSame([], $group->getNormal());
        $this->assertSame([], $group->getShiny());
    }
}
