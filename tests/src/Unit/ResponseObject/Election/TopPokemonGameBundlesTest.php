<?php

declare(strict_types=1);

namespace App\Tests\Unit\ResponseObject\Election;

use App\ResponseObject\Election\TopPokemonGameBundles;
use App\ResponseObject\Election\TopPokemonSlugRef;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(TopPokemonGameBundles::class)]
final class TopPokemonGameBundlesTest extends TestCase
{
    public function testConstructor(): void
    {
        $ref1 = new TopPokemonSlugRef('redgreenblueyellow');
        $ref2 = new TopPokemonSlugRef('goldsilvercrystal');
        $object = new TopPokemonGameBundles([$ref1], [$ref2]);

        $this->assertSame([$ref1], $object->getNormal());
        $this->assertSame([$ref2], $object->getShiny());
    }

    public function testEmpty(): void
    {
        $object = new TopPokemonGameBundles([], []);

        $this->assertSame([], $object->getNormal());
        $this->assertSame([], $object->getShiny());
    }
}
