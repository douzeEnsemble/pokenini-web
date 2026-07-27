<?php

declare(strict_types=1);

namespace App\Tests\Unit\ResponseObject\Common;

use App\ResponseObject\Common\CreditImage;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(CreditImage::class)]
final class CreditImageTest extends TestCase
{
    public function testGettersExposeConstructorValues(): void
    {
        $image = new CreditImage(
            pokemonSlug: 'bulbasaur',
            pokemonName: 'Bulbasaur',
            pokemonFrenchName: 'Bulbizarre',
            pokemonIcon: 'bulbasaur',
            size: 'small',
            isShiny: true,
        );

        $this->assertSame('bulbasaur', $image->getPokemonSlug());
        $this->assertSame('Bulbasaur', $image->getPokemonName());
        $this->assertSame('Bulbizarre', $image->getPokemonFrenchName());
        $this->assertSame('bulbasaur', $image->getPokemonIcon());
        $this->assertSame('small', $image->getSize());
        $this->assertTrue($image->isShiny());
    }
}
