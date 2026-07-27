<?php

declare(strict_types=1);

namespace App\Tests\Unit\ResponseObject\Common;

use App\ResponseObject\Common\PokemonCredit;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(PokemonCredit::class)]
final class PokemonCreditTest extends TestCase
{
    public function testGettersExtractNameAndUrlFromMergedCredit(): void
    {
        $credit = new PokemonCredit(credit: 'PokéSprite - https://github.com/msikma/pokesprite');

        $this->assertSame('PokéSprite', $credit->getName());
        $this->assertSame('https://github.com/msikma/pokesprite', $credit->getUrl());
    }

    public function testGettersFallBackToFullCreditWhenNoUrlPresent(): void
    {
        $credit = new PokemonCredit(credit: 'PokéSprite');

        $this->assertSame('PokéSprite', $credit->getName());
        $this->assertNull($credit->getUrl());
    }

    public function testGettersFallBackToRawCreditWhenOnlyUrlPresent(): void
    {
        $credit = new PokemonCredit(credit: 'https://serebii.net');

        $this->assertSame('https://serebii.net', $credit->getName());
        $this->assertSame('https://serebii.net', $credit->getUrl());
    }

    public function testExtractUrlIsPubliclyCallable(): void
    {
        $this->assertSame(
            'https://github.com/msikma/pokesprite',
            PokemonCredit::extractUrl('PokéSprite - https://github.com/msikma/pokesprite'),
        );
    }

    public function testExtractNameIsPubliclyCallable(): void
    {
        $this->assertSame(
            'PokéSprite',
            PokemonCredit::extractName('PokéSprite - https://github.com/msikma/pokesprite', 'https://github.com/msikma/pokesprite'),
        );
    }
}
