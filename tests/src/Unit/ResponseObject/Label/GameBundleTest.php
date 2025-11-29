<?php

declare(strict_types=1);

namespace App\Tests\Unit\ResponseObject\Label;

use App\ResponseObject\Label\GameBundle;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(GameBundle::class)]
class GameBundleTest extends TestCase
{
    public function testConstructor(): void
    {
        $object = new GameBundle(
            'Toto',
            'Tautaux',
            'toto',
            'gen_y',
        );

        $this->assertSame('Toto', $object->getName());
        $this->assertSame('Tautaux', $object->getFrenchName());
        $this->assertSame('toto', $object->getSlug());
        $this->assertSame('gen_y', $object->getGenerationSlug());
    }
}
