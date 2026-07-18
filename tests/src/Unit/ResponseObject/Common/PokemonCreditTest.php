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
    public function testGetters(): void
    {
        $credit = new PokemonCredit(name: 'PokéSprite', url: 'https://github.com/msikma/pokesprite');

        $this->assertSame('PokéSprite', $credit->getName());
        $this->assertSame('https://github.com/msikma/pokesprite', $credit->getUrl());
    }
}
