<?php

declare(strict_types=1);

namespace App\Tests\Unit\ResponseObject\Common;

use App\ResponseObject\Common\PokemonCredit;
use App\ResponseObject\Common\PokemonCreditRow;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(PokemonCreditRow::class)]
final class PokemonCreditRowTest extends TestCase
{
    #[Test]
    public function gettersExposeConstructorValues(): void
    {
        $smallRegular = new PokemonCredit(credit: 'PokéSprite - https://github.com/msikma/pokesprite');
        $bigRegular = new PokemonCredit(credit: 'PokemonDB - https://pokemondb.net/sprites/bulbasaur');

        $row = new PokemonCreditRow(
            pokemonSlug: 'bulbasaur',
            pokemonName: 'Bulbasaur',
            pokemonFrenchName: 'Bulbizarre',
            pokemonIcon: 'bulbasaur',
            smallRegularCredit: $smallRegular,
            smallShinyCredit: null,
            bigRegularCredit: $bigRegular,
            bigShinyCredit: null,
        );

        $this->assertSame('bulbasaur', $row->getPokemonSlug());
        $this->assertSame('Bulbasaur', $row->getPokemonName());
        $this->assertSame('Bulbizarre', $row->getPokemonFrenchName());
        $this->assertSame('bulbasaur', $row->getPokemonIcon());
        $this->assertSame($smallRegular, $row->getSmallRegularCredit());
        $this->assertNull($row->getSmallShinyCredit());
        $this->assertSame($bigRegular, $row->getBigRegularCredit());
        $this->assertNull($row->getBigShinyCredit());
    }

    #[DataProvider('providerExactlyOneSlotSet')]
    #[Test]
    public function hasAnyCreditIsTrueWhenExactlyOneSlotIsSet(
        ?PokemonCredit $smallRegularCredit,
        ?PokemonCredit $smallShinyCredit,
        ?PokemonCredit $bigRegularCredit,
        ?PokemonCredit $bigShinyCredit,
    ): void {
        $row = new PokemonCreditRow(
            pokemonSlug: 'ivysaur',
            pokemonName: 'Ivysaur',
            pokemonFrenchName: 'Herbizarre',
            pokemonIcon: 'ivysaur',
            smallRegularCredit: $smallRegularCredit,
            smallShinyCredit: $smallShinyCredit,
            bigRegularCredit: $bigRegularCredit,
            bigShinyCredit: $bigShinyCredit,
        );

        $this->assertTrue($row->hasAnyCredit());
    }

    /**
     * @return array<string, array{?PokemonCredit, ?PokemonCredit, ?PokemonCredit, ?PokemonCredit}>
     */
    public static function providerExactlyOneSlotSet(): array
    {
        $credit = new PokemonCredit(credit: 'Serebii - https://serebii.net');

        return [
            'smallRegularCredit only' => [$credit, null, null, null],
            'smallShinyCredit only' => [null, $credit, null, null],
            'bigRegularCredit only' => [null, null, $credit, null],
            'bigShinyCredit only' => [null, null, null, $credit],
        ];
    }

    #[Test]
    public function hasAnyCreditIsFalseWhenAllFourSlotsAreNull(): void
    {
        $row = new PokemonCreditRow(
            pokemonSlug: 'venusaur',
            pokemonName: 'Venusaur',
            pokemonFrenchName: 'Florizarre',
            pokemonIcon: 'venusaur',
            smallRegularCredit: null,
            smallShinyCredit: null,
            bigRegularCredit: null,
            bigShinyCredit: null,
        );

        $this->assertFalse($row->hasAnyCredit());
    }
}
