<?php

declare(strict_types=1);

namespace App\Tests\Unit\ResponseObject\Election;

use App\ResponseObject\Election\TopPokemonInfo;
use App\ResponseObject\Election\TopPokemonLabels;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(TopPokemonInfo::class)]
final class TopPokemonInfoTest extends TestCase
{
    public function testConstructor(): void
    {
        $labels = new TopPokemonLabels('Mega Venusaur', 'Mega Florizarre');
        $object = new TopPokemonInfo('venusaur-mega', $labels, 3);

        $this->assertSame('venusaur-mega', $object->getSlug());
        $this->assertSame($labels, $object->getLabels());
        $this->assertSame(3, $object->getNationalDexNumber());
    }
}
