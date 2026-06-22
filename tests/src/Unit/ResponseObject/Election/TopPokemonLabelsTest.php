<?php

declare(strict_types=1);

namespace App\Tests\Unit\ResponseObject\Election;

use App\ResponseObject\Election\TopPokemonLabels;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(TopPokemonLabels::class)]
final class TopPokemonLabelsTest extends TestCase
{
    public function testConstructor(): void
    {
        $object = new TopPokemonLabels('Mega Venusaur', 'Mega Florizarre');

        $this->assertSame('Mega Venusaur', $object->getName());
        $this->assertSame('Mega Florizarre', $object->getFrenchName());
    }
}
