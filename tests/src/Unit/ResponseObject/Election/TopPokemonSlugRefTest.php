<?php

declare(strict_types=1);

namespace App\Tests\Unit\ResponseObject\Election;

use App\ResponseObject\Election\TopPokemonSlugRef;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(TopPokemonSlugRef::class)]
final class TopPokemonSlugRefTest extends TestCase
{
    #[Test]
    public function constructor(): void
    {
        $object = new TopPokemonSlugRef('bulbasaur');

        $this->assertSame('bulbasaur', $object->getSlug());
    }
}
