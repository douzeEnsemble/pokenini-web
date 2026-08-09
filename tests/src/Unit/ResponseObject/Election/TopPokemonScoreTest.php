<?php

declare(strict_types=1);

namespace App\Tests\Unit\ResponseObject\Election;

use App\ResponseObject\Election\TopPokemonScore;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(TopPokemonScore::class)]
final class TopPokemonScoreTest extends TestCase
{
    #[Test]
    public function constructor(): void
    {
        $object = new TopPokemonScore(1250.5, true);

        $this->assertSame(1250.5, $object->getElo());
        $this->assertTrue($object->isSignificance());
    }
}
