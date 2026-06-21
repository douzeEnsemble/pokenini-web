<?php

declare(strict_types=1);

namespace App\Tests\Unit\ResponseObject\Label;

use App\ResponseObject\Label\GameBundle;
use App\ResponseObject\Label\Generation;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(GameBundle::class)]
final class GameBundleTest extends TestCase
{
    public function testConstructor(): void
    {
        $object = new GameBundle(
            'Toto',
            'Tautaux',
            'toto',
            new Generation('gen_y'),
        );

        $this->assertSame('Toto', $object->getName());
        $this->assertSame('Tautaux', $object->getFrenchName());
        $this->assertSame('toto', $object->getSlug());
        $this->assertSame('gen_y', $object->getGenerationSlug());
    }
}
