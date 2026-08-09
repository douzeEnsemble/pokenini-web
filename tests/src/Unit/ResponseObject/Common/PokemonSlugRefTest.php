<?php

declare(strict_types=1);

namespace App\Tests\Unit\ResponseObject\Common;

use App\ResponseObject\Common\PokemonSlugRef;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(PokemonSlugRef::class)]
final class PokemonSlugRefTest extends TestCase
{
    #[Test]
    public function getSlug(): void
    {
        $ref = new PokemonSlugRef('bulbasaur');

        $this->assertSame('bulbasaur', $ref->getSlug());
    }
}
