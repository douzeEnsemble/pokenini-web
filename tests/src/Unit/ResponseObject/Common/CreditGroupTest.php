<?php

declare(strict_types=1);

namespace App\Tests\Unit\ResponseObject\Common;

use App\ResponseObject\Common\CreditGroup;
use App\ResponseObject\Common\CreditImage;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(CreditGroup::class)]
final class CreditGroupTest extends TestCase
{
    public function testGettersExtractNameAndUrlAndExposeImages(): void
    {
        $image = new CreditImage(
            pokemonSlug: 'bulbasaur',
            pokemonName: 'Bulbasaur',
            pokemonFrenchName: 'Bulbizarre',
            pokemonIcon: 'bulbasaur',
            size: 'small',
            isShiny: false,
        );

        $group = new CreditGroup(
            credit: 'PokéSprite - https://github.com/msikma/pokesprite',
            images: [$image],
        );

        $this->assertSame('PokéSprite', $group->getName());
        $this->assertSame('https://github.com/msikma/pokesprite', $group->getUrl());
        $this->assertSame([$image], $group->getImages());
    }

    public function testGetUrlReturnsNullWhenCreditHasNoUrl(): void
    {
        $group = new CreditGroup(credit: 'PokéSprite', images: []);

        $this->assertNull($group->getUrl());
        $this->assertSame('PokéSprite', $group->getName());
    }
}
